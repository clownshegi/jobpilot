<?php

use App\Actions\ImportVacancy;
use App\Enums\ApplicationStatus;
use App\Enums\ImportStatus;
use App\Events\BoardChanged;
use App\Events\VacancyImportUpdated;
use App\Jobs\ImportVacancyFromUrl;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyImport;
use App\Support\UrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const IMPORT_URL = 'https://jobs.example/vacancy/42';

beforeEach(function () {
    // No real DNS in tests: "internal.test" plays an internal host, everything else is public.
    app()->instance(UrlGuard::class, new class extends UrlGuard
    {
        protected function resolve(string $host): array
        {
            return $host === 'internal.test' ? ['10.0.0.5'] : ['93.184.215.14'];
        }
    });
});

function jobPage(): string
{
    $jsonLd = json_encode([
        '@type' => 'JobPosting',
        'title' => 'Middle Laravel Developer',
        'hiringOrganization' => ['name' => 'Moravia Data Labs'],
        'description' => '<p>Laravel, Vue, Postgres.</p>',
        'jobLocation' => ['address' => ['addressLocality' => 'Brno']],
        'baseSalary' => ['currency' => 'CZK', 'value' => ['minValue' => 70000, 'maxValue' => 90000]],
    ]);

    return "<html><head><script type=\"application/ld+json\">{$jsonLd}</script></head><body></body></html>";
}

function runImport(User $user, string $url = IMPORT_URL): VacancyImport
{
    $import = $user->vacancyImports()->create(['url' => $url]);
    app(ImportVacancy::class)->handle($import);

    return $import->refresh();
}

test('submitting a link queues an import and flashes it to the page', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('board'))
        ->post(route('vacancy-imports.store'), ['url' => IMPORT_URL])
        ->assertRedirect(route('board'))
        ->assertSessionHas('vacancyImport', fn (array $flashed) => $flashed['status'] === 'pending');

    $import = $user->vacancyImports()->sole();
    expect($import->url)->toBe(IMPORT_URL);
    Queue::assertPushed(ImportVacancyFromUrl::class, fn ($job) => $job->import->is($import));
});

test('the link is validated', function (string $url) {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->post(route('vacancy-imports.store'), ['url' => $url])
        ->assertSessionHasErrors('url');

    Queue::assertNothingPushed();
})->with([
    'empty' => '',
    'not a url' => 'djinni vacancy',
    'ftp' => 'ftp://jobs.example/1',
    'internal host' => 'http://internal.test/admin',
    'loopback' => 'http://127.0.0.1:8000',
]);

test('an import creates the vacancy, its company and a wishlist application', function () {
    Http::fake(['jobs.example/*' => Http::response(jobPage())]);
    Event::fake([VacancyImportUpdated::class, BoardChanged::class]);
    $user = User::factory()->create();

    $import = runImport($user);

    $vacancy = $user->vacancies()->sole();
    expect($import->status)->toBe(ImportStatus::Done)
        ->and($import->vacancy_id)->toBe($vacancy->id)
        ->and($vacancy)
        ->title->toBe('Middle Laravel Developer')
        ->url->toBe(IMPORT_URL)
        ->location->toBe('Brno')
        ->salary_min->toBe(70000)
        ->salary_currency->toBe('CZK')
        ->source->toBe('jobs.example')
        ->description->toBe('Laravel, Vue, Postgres.')
        ->and($vacancy->company->name)->toBe('Moravia Data Labs')
        ->and($vacancy->application->status)->toBe(ApplicationStatus::Wishlist);

    // processing, then done
    Event::assertDispatchedTimes(VacancyImportUpdated::class, 2);
    Event::assertDispatched(BoardChanged::class, fn (BoardChanged $event) => $event->userId === $user->id);
});

test('importing a link that is already on the board points to the existing vacancy', function () {
    Http::fake();
    $user = User::factory()->create();
    $existing = Vacancy::factory()->for($user)->create(['url' => IMPORT_URL]);

    $import = runImport($user);

    expect($import->status)->toBe(ImportStatus::Done)
        ->and($import->vacancy_id)->toBe($existing->id)
        ->and($import->message)->toContain('already')
        ->and($user->vacancies()->count())->toBe(1);
    Http::assertNothingSent();
});

test('a page without a job posting fails with a helpful message', function () {
    Http::fake(['*' => Http::response('<html><body>Login required</body></html>')]);

    $import = runImport(User::factory()->create());

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->message)->toContain("Couldn't find a job posting")
        ->and(Vacancy::count())->toBe(0);
});

test('an error response fails the import', function () {
    Http::fake(['*' => Http::response('Not found', 404)]);

    $import = runImport(User::factory()->create());

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->message)->toContain('HTTP 404');
});

test('an unreachable page fails the import', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $import = runImport(User::factory()->create());

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->message)->toContain("Couldn't reach the page");
});

test('the job is not fetched when the address became internal after validation', function () {
    Http::fake();

    $import = runImport(User::factory()->create(), 'http://internal.test/vacancy');

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->message)->toBe('This address is not allowed.');
    Http::assertNothingSent();
});

test('an unexpected job failure is reported to the user', function () {
    Event::fake([VacancyImportUpdated::class]);
    $import = User::factory()->create()->vacancyImports()->create(['url' => IMPORT_URL]);

    (new ImportVacancyFromUrl($import))->failed(new RuntimeException('boom'));

    expect($import->refresh()->status)->toBe(ImportStatus::Failed);
    Event::assertDispatched(VacancyImportUpdated::class);
});

test('import updates are broadcast privately to the owner', function () {
    $import = User::factory()->create()->vacancyImports()->create(['url' => IMPORT_URL]);
    $event = new VacancyImportUpdated($import);

    expect($event->broadcastOn()->name)->toBe("private-App.Models.User.{$import->user_id}")
        ->and($event->broadcastAs())->toBe('vacancy-import.updated')
        ->and($event->broadcastWith())->toMatchArray(['id' => $import->id, 'status' => 'pending']);
});
