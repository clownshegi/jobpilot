<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Vacancy;
use Inertia\Testing\AssertableInertia as Assert;

function vacancyPayload(array $overrides = []): array
{
    return [
        'title' => 'Laravel Developer',
        'company_name' => 'Acme',
        'url' => 'https://jobs.example/laravel',
        'location' => 'Brno',
        'work_mode' => 'hybrid',
        'salary_min' => 2500,
        'salary_max' => 3500,
        'salary_currency' => 'eur',
        'source' => 'linkedin',
        'description' => 'We use Laravel and Vue.',
        'status' => 'wishlist',
        'notes' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('vacancies.index'))->assertRedirect(route('login'));
});

test('creating a vacancy creates its company and a wishlist application', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('vacancies.store'), vacancyPayload());

    $vacancy = $user->vacancies()->sole();
    $response->assertRedirect(route('vacancies.show', $vacancy));

    expect($vacancy)
        ->title->toBe('Laravel Developer')
        ->salary_currency->toBe('EUR')
        ->and($vacancy->company->name)->toBe('Acme')
        ->and($vacancy->company->user_id)->toBe($user->id)
        ->and($vacancy->application->status)->toBe(ApplicationStatus::Wishlist)
        ->and($vacancy->application->applied_at)->toBeNull()
        ->and($vacancy->application->statusChanges()->sole()->to_status)->toBe(ApplicationStatus::Wishlist);
});

test('an existing company is reused, ignoring case', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme']);

    $this->actingAs($user)->post(route('vacancies.store'), vacancyPayload(['company_name' => ' acme ']));

    expect($user->companies()->count())->toBe(1)
        ->and($user->vacancies()->sole()->company_id)->toBe($company->id);
});

test('a vacancy added as applied gets applied_at and goes to the end of its column', function () {
    $user = User::factory()->create();
    JobApplication::factory()
        ->for(Vacancy::factory()->for($user))
        ->status(ApplicationStatus::Applied, position: 0)
        ->create();

    $this->actingAs($user)->post(route('vacancies.store'), vacancyPayload(['status' => 'applied']));

    $application = $user->vacancies()->latest('id')->first()->application;

    expect($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->position)->toBe(1)
        ->and($application->applied_at)->not->toBeNull();
});

test('vacancy fields are validated', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('vacancies.store'), vacancyPayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'missing company' => [['company_name' => ''], 'company_name'],
    'invalid url' => [['url' => 'nope'], 'url'],
    'unknown work mode' => [['work_mode' => 'moon'], 'work_mode'],
    'max below min' => [['salary_min' => 3000, 'salary_max' => 2000], 'salary_max'],
    'salary without currency' => [['salary_currency' => null], 'salary_currency'],
    'unknown status' => [['status' => 'hired'], 'status'],
]);

test('index shows only the user\'s vacancies and filters them', function () {
    $user = User::factory()->create();
    $laravel = Vacancy::factory()->for($user)->create(['title' => 'Laravel Developer']);
    JobApplication::factory()->for($laravel)->status(ApplicationStatus::Interview)->create();
    $python = Vacancy::factory()->for($user)->create(['title' => 'Python Developer']);
    JobApplication::factory()->for($python)->create();
    Vacancy::factory()->create(['title' => 'Laravel at another user']);

    $this->actingAs($user)
        ->get(route('vacancies.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('vacancies/Index')
            ->has('vacancies.data', 2));

    $this->actingAs($user)
        ->get(route('vacancies.index', ['search' => 'laravel']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('vacancies.data', 1)
            ->where('vacancies.data.0.id', $laravel->id));

    $this->actingAs($user)
        ->get(route('vacancies.index', ['status' => 'wishlist']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('vacancies.data', 1)
            ->where('vacancies.data.0.id', $python->id));
});

test('a vacancy page shows the vacancy with its application history', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for(Vacancy::factory()->for($user))->create();
    $application->statusChanges()->create(['to_status' => ApplicationStatus::Wishlist]);

    $this->actingAs($user)
        ->get(route('vacancies.show', $application->vacancy))
        ->assertInertia(fn (Assert $page) => $page
            ->component('vacancies/Show')
            ->where('vacancy.id', $application->vacancy_id)
            ->where('application.status', 'wishlist')
            ->has('application.status_changes', 1));
});

test('a vacancy can be updated and moved to another company', function () {
    $user = User::factory()->create();
    $vacancy = Vacancy::factory()->for($user)->create();
    JobApplication::factory()->for($vacancy)->create();

    $this->actingAs($user)
        ->put(route('vacancies.update', $vacancy), vacancyPayload(['title' => 'Senior Laravel Developer', 'company_name' => 'Globex']))
        ->assertRedirect(route('vacancies.show', $vacancy));

    $vacancy->refresh();
    expect($vacancy->title)->toBe('Senior Laravel Developer')
        ->and($vacancy->company->name)->toBe('Globex');
});

test('deleting a vacancy deletes its application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for(Vacancy::factory()->for($user))->create();

    $this->actingAs($user)
        ->delete(route('vacancies.destroy', $application->vacancy))
        ->assertRedirect(route('vacancies.index'));

    expect(Vacancy::count())->toBe(0)
        ->and(JobApplication::count())->toBe(0);
});

test('users cannot see or change other users\' vacancies', function () {
    $vacancy = Vacancy::factory()->create();
    JobApplication::factory()->for($vacancy)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get(route('vacancies.show', $vacancy))->assertForbidden();
    $this->actingAs($intruder)->get(route('vacancies.edit', $vacancy))->assertForbidden();
    $this->actingAs($intruder)->put(route('vacancies.update', $vacancy), vacancyPayload())->assertForbidden();
    $this->actingAs($intruder)->delete(route('vacancies.destroy', $vacancy))->assertForbidden();

    expect(Vacancy::count())->toBe(1);
});
