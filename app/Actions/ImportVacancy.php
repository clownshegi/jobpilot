<?php

namespace App\Actions;

use App\Enums\ImportStatus;
use App\Events\VacancyImportUpdated;
use App\Exceptions\VacancyImportException;
use App\Models\Vacancy;
use App\Models\VacancyImport;
use App\Services\JobPosting\JobPostingExtractor;
use App\Support\UrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\UriInterface;

/**
 * Fetches the page behind an import's URL and turns it into a vacancy on the user's board.
 * Every status change is broadcast, so the browser can follow the import live.
 */
class ImportVacancy
{
    public function __construct(
        private CreateVacancy $createVacancy,
        private JobPostingExtractor $extractor,
        private UrlGuard $urlGuard,
    ) {}

    public function handle(VacancyImport $import): void
    {
        $this->update($import, ImportStatus::Processing);

        try {
            $existing = $import->user->vacancies()->where('url', $import->url)->first();

            if ($existing !== null) {
                $this->update($import, ImportStatus::Done, $existing, 'This vacancy is already in your list.');

                return;
            }

            $posting = $this->extractor->extract($this->fetch($import->url), $import->url)
                ?? throw new VacancyImportException("Couldn't find a job posting on this page. Add it manually instead.");

            $source = preg_replace('/^www\./', '', (string) parse_url($import->url, PHP_URL_HOST));
            $vacancy = $this->createVacancy->handle($import->user, $posting->toVacancyData($import->url, $source));

            $this->update($import, ImportStatus::Done, $vacancy);
        } catch (VacancyImportException $exception) {
            $this->fail($import, $exception->getMessage());
        } catch (ConnectionException) {
            $this->fail($import, "Couldn't reach the page. Check the link and try again.");
        }
    }

    public function fail(VacancyImport $import, string $message): void
    {
        $this->update($import, ImportStatus::Failed, message: $message);
    }

    private function fetch(string $url): string
    {
        if (! $this->urlGuard->isAllowed($url)) {
            throw new VacancyImportException('This address is not allowed.');
        }

        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; JobPilot/1.0)', 'Accept' => 'text/html'])
            ->withOptions(['allow_redirects' => [
                'max' => 5,
                // A public URL may redirect to an internal one, so redirect targets get the same check.
                'on_redirect' => function ($request, $response, UriInterface $target) {
                    if (! $this->urlGuard->isAllowed((string) $target)) {
                        throw new VacancyImportException('The page redirected to an address that is not allowed.');
                    }
                },
            ]])
            ->get($url);

        if ($response->failed()) {
            throw new VacancyImportException("The page responded with an error (HTTP {$response->status()}).");
        }

        return $response->body();
    }

    private function update(VacancyImport $import, ImportStatus $status, ?Vacancy $vacancy = null, ?string $message = null): void
    {
        $import->update(['status' => $status, 'vacancy_id' => $vacancy?->id, 'message' => $message]);

        VacancyImportUpdated::dispatch($import);
    }
}
