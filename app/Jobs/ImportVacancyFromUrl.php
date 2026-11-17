<?php

namespace App\Jobs;

use App\Actions\ImportVacancy;
use App\Models\VacancyImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ImportVacancyFromUrl implements ShouldQueue
{
    use Queueable;

    /** Expected failures (bad link, no posting) are handled inside; retrying wouldn't help. */
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public VacancyImport $import) {}

    public function handle(ImportVacancy $importVacancy): void
    {
        $importVacancy->handle($this->import);
    }

    /**
     * An unexpected error: still tell the user instead of leaving the import "processing" forever.
     */
    public function failed(?Throwable $exception): void
    {
        app(ImportVacancy::class)->fail($this->import, 'Something went wrong while importing. Try again later.');
    }
}
