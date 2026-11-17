<?php

namespace Database\Seeders;

use App\Actions\CreateVacancy;
use App\Actions\MoveJobApplication;
use App\Enums\ApplicationStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for local development and screenshots.
 * Log in as demo@jobpilot.test with the password "password" (the UserFactory default).
 * Company names are fictional.
 */
class DatabaseSeeder extends Seeder
{
    public function run(CreateVacancy $createVacancy, MoveJobApplication $moveJobApplication): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@jobpilot.test',
        ]);

        $vacancies = [
            ['Middle Laravel Developer', 'Northwind Software', 'Kyiv', 'remote', 2500, 3500, 'USD', 'djinni', 'applied'],
            ['PHP/Laravel Engineer', 'Bluebird HR Tech', 'Lviv', 'hybrid', 2800, 3800, 'USD', 'dou', 'interview'],
            ['Full-stack Developer (Laravel + Vue)', 'Copperleaf Studio', 'Kyiv', 'remote', 3000, 4000, 'USD', 'djinni', 'screening'],
            ['Backend Developer (Python)', 'Moravia Data Labs', 'Brno', 'hybrid', 75000, 95000, 'CZK', 'jobs.cz', 'wishlist'],
            ['Python Developer, AI Platform', 'Spilberk AI', 'Brno', 'onsite', 80000, 110000, 'CZK', 'linkedin', 'wishlist'],
            ['Senior PHP Developer', 'Harbor Logistics', 'Prague', 'remote', 4000, 5000, 'EUR', 'linkedin', 'rejected'],
            ['Laravel Developer', 'Greenfield Commerce', 'Warsaw', 'remote', 3200, 4200, 'EUR', 'linkedin', 'applied'],
            ['Vue.js Frontend Developer', 'Northwind Software', 'Kyiv', 'remote', 2200, 3000, 'USD', 'djinni', 'wishlist'],
            ['Backend Engineer (PHP)', 'Atlas Fintech', 'Kyiv', 'hybrid', 3500, 4500, 'USD', 'dou', 'offer'],
            ['Junior AI Engineer (RAG)', 'Moravia Data Labs', 'Brno', 'hybrid', 60000, 80000, 'CZK', 'startupjobs', 'wishlist'],
        ];

        foreach ($vacancies as [$title, $company, $location, $workMode, $min, $max, $currency, $source, $status]) {
            $vacancy = $createVacancy->handle($user, [
                'title' => $title,
                'company_name' => $company,
                'location' => $location,
                'work_mode' => $workMode,
                'salary_min' => $min,
                'salary_max' => $max,
                'salary_currency' => $currency,
                'source' => $source,
                'url' => null,
                'description' => "We are looking for a {$title} to join {$company}.",
                'status' => ApplicationStatus::Wishlist->value,
            ]);

            // Walk the pipeline step by step so the status history looks real.
            foreach ($this->pathTo(ApplicationStatus::from($status)) as $step) {
                $moveJobApplication->handle($vacancy->application()->sole(), $step);
            }
        }
    }

    /**
     * @return list<ApplicationStatus>
     */
    private function pathTo(ApplicationStatus $target): array
    {
        $pipeline = [ApplicationStatus::Applied, ApplicationStatus::Screening, ApplicationStatus::Interview, ApplicationStatus::Offer];

        return match ($target) {
            ApplicationStatus::Wishlist => [],
            ApplicationStatus::Rejected => [ApplicationStatus::Applied, ApplicationStatus::Screening, ApplicationStatus::Rejected],
            default => array_slice($pipeline, 0, array_search($target, $pipeline, true) + 1),
        };
    }
}
