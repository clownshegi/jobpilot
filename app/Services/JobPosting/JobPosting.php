<?php

namespace App\Services\JobPosting;

use App\Enums\WorkMode;

/**
 * Vacancy data found on a web page.
 */
final readonly class JobPosting
{
    public function __construct(
        public string $title,
        public string $company,
        public ?string $description = null,
        public ?string $location = null,
        public ?WorkMode $workMode = null,
        public ?int $salaryMin = null,
        public ?int $salaryMax = null,
        public ?string $salaryCurrency = null,
    ) {}

    /**
     * Input for the CreateVacancy action.
     *
     * @return array<string, mixed>
     */
    public function toVacancyData(string $url, string $source): array
    {
        $hasSalary = $this->salaryMin !== null || $this->salaryMax !== null;

        return [
            'title' => mb_substr($this->title, 0, 255),
            'company_name' => mb_substr($this->company, 0, 255),
            'url' => $url,
            'location' => $this->location !== null ? mb_substr($this->location, 0, 255) : null,
            'work_mode' => $this->workMode?->value,
            'salary_min' => $this->salaryMin,
            'salary_max' => $this->salaryMax,
            'salary_currency' => $hasSalary ? $this->salaryCurrency : null,
            'source' => mb_substr($source, 0, 50),
            'description' => $this->description !== null ? mb_substr($this->description, 0, 50000) : null,
            'status' => 'wishlist',
        ];
    }
}
