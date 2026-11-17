<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'status' => ApplicationStatus::Wishlist,
            'position' => 0,
            'applied_at' => null,
            'notes' => null,
        ];
    }

    public function status(ApplicationStatus $status, int $position = 0): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'position' => $position,
            'applied_at' => $status === ApplicationStatus::Wishlist ? null : now(),
        ]);
    }
}
