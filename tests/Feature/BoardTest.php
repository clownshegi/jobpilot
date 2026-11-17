<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Vacancy;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Creates applications for the user in the given column, positions 0..n-1.
 *
 * @return list<JobApplication>
 */
function column(User $user, ApplicationStatus $status, int $count): array
{
    return collect(range(0, $count - 1))
        ->map(fn (int $position) => JobApplication::factory()
            ->for(Vacancy::factory()->for($user))
            ->status($status, $position)
            ->create())
        ->all();
}

function positions(User $user, ApplicationStatus $status): array
{
    return JobApplication::query()->ownedBy($user)->where('status', $status)
        ->orderBy('position')->pluck('id')->all();
}

test('the board groups the user\'s applications by status in position order', function () {
    $user = User::factory()->create();
    [$first, $second] = column($user, ApplicationStatus::Applied, 2);
    $second->update(['position' => 0]);
    $first->update(['position' => 1]);
    column(User::factory()->create(), ApplicationStatus::Applied, 1);

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Board')
            ->has('columns', count(ApplicationStatus::cases()))
            ->where('columns.1.status', 'applied')
            ->has('columns.1.applications', 2)
            ->where('columns.1.applications.0.id', $second->id)
            ->where('columns.1.applications.1.id', $first->id)
            ->has('columns.0.applications', 0));
});

test('moving to another column inserts at the position and renumbers both columns', function () {
    $user = User::factory()->create();
    [$w0, $w1, $w2] = column($user, ApplicationStatus::Wishlist, 3);
    [$a0, $a1] = column($user, ApplicationStatus::Applied, 2);

    $this->actingAs($user)
        ->patch(route('job-applications.move', $w1), ['status' => 'applied', 'position' => 1])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(positions($user, ApplicationStatus::Applied))->toBe([$a0->id, $w1->id, $a1->id])
        ->and(positions($user, ApplicationStatus::Wishlist))->toBe([$w0->id, $w2->id])
        ->and(JobApplication::find($w2->id)->position)->toBe(1);
});

test('moving within a column reorders it without recording a status change', function () {
    $user = User::factory()->create();
    [$first, $second, $third] = column($user, ApplicationStatus::Interview, 3);

    $this->actingAs($user)->patch(route('job-applications.move', $third), ['status' => 'interview', 'position' => 0]);

    expect(positions($user, ApplicationStatus::Interview))->toBe([$third->id, $first->id, $second->id])
        ->and($third->statusChanges()->count())->toBe(0);
});

test('a status change is recorded and the first move out of the wishlist sets applied_at', function () {
    $user = User::factory()->create();
    [$application] = column($user, ApplicationStatus::Wishlist, 1);

    $this->actingAs($user)->patch(route('job-applications.move', $application), ['status' => 'applied']);

    $application->refresh();
    $change = $application->statusChanges()->sole();

    expect($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->applied_at)->not->toBeNull()
        ->and($change->from_status)->toBe(ApplicationStatus::Wishlist)
        ->and($change->to_status)->toBe(ApplicationStatus::Applied);
});

test('without a position the application goes to the end of the column', function () {
    $user = User::factory()->create();
    column($user, ApplicationStatus::Offer, 2);
    [$application] = column($user, ApplicationStatus::Interview, 1);

    $this->actingAs($user)->patch(route('job-applications.move', $application), ['status' => 'offer']);

    expect($application->fresh()->position)->toBe(2);
});

test('an out-of-range position is clamped to the end of the column', function () {
    $user = User::factory()->create();
    [$application] = column($user, ApplicationStatus::Wishlist, 1);

    $this->actingAs($user)->patch(route('job-applications.move', $application), ['status' => 'rejected', 'position' => 99]);

    expect($application->fresh()->position)->toBe(0);
});

test('the move request is validated', function () {
    $user = User::factory()->create();
    [$application] = column($user, ApplicationStatus::Wishlist, 1);

    $this->actingAs($user)
        ->patch(route('job-applications.move', $application), ['status' => 'hired', 'position' => -1])
        ->assertSessionHasErrors(['status', 'position']);
});

test('application notes can be updated', function () {
    $user = User::factory()->create();
    [$application] = column($user, ApplicationStatus::Applied, 1);

    $this->actingAs($user)
        ->patch(route('job-applications.update', $application), ['notes' => 'Recruiter: Anna, call on Friday'])
        ->assertSessionHasNoErrors();

    expect($application->fresh()->notes)->toBe('Recruiter: Anna, call on Friday');
});

test('users cannot move or edit other users\' applications', function () {
    [$application] = column(User::factory()->create(), ApplicationStatus::Wishlist, 1);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->patch(route('job-applications.move', $application), ['status' => 'offer'])
        ->assertForbidden();
    $this->actingAs($intruder)
        ->patch(route('job-applications.update', $application), ['notes' => 'x'])
        ->assertForbidden();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Wishlist);
});
