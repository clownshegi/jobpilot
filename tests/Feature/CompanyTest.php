<?php

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});

test('index lists only the current user\'s companies with vacancy counts', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme']);
    Vacancy::factory()->count(2)->for($user)->for($company)->create();
    Company::factory()->create(['name' => 'Someone else\'s company']);

    $this->actingAs($user)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Index')
            ->has('companies', 1)
            ->where('companies.0.name', 'Acme')
            ->where('companies.0.vacancies_count', 2));
});

test('a company can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Acme',
            'website' => 'https://acme.example',
            'location' => 'Brno',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->companies()->sole())
        ->name->toBe('Acme')
        ->location->toBe('Brno');
});

test('company name is required and unique per user', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Acme']);

    $this->actingAs($user)
        ->post(route('companies.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    $this->actingAs($user)
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasErrors('name');

    // Another user may have a company with the same name.
    $this->actingAs(User::factory()->create())
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasNoErrors();
});

test('website must be a valid url', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('companies.store'), ['name' => 'Acme', 'website' => 'not a url'])
        ->assertSessionHasErrors('website');
});

test('a company can be updated, keeping its own name', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme']);

    $this->actingAs($user)
        ->put(route('companies.update', $company), ['name' => 'Acme', 'location' => 'Prague'])
        ->assertSessionHasNoErrors();

    expect($company->fresh()->location)->toBe('Prague');
});

test('deleting a company deletes its vacancies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    Vacancy::factory()->for($user)->for($company)->create();

    $this->actingAs($user)
        ->delete(route('companies.destroy', $company))
        ->assertRedirect(route('companies.index'));

    expect(Company::count())->toBe(0)
        ->and(Vacancy::count())->toBe(0);
});

test('users cannot change other users\' companies', function () {
    $company = Company::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('companies.update', $company), ['name' => 'Hacked'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('companies.destroy', $company))
        ->assertForbidden();

    expect($company->fresh()->name)->not->toBe('Hacked');
});
