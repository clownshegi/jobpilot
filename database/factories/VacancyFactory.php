<?php

namespace Database\Factories;

use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    public function definition(): array
    {
        $salaryMin = fake()->optional()->numberBetween(15, 40);

        return [
            'user_id' => User::factory(),
            // The company always belongs to the same user as the vacancy.
            'company_id' => fn (array $attributes) => Company::factory()->state(['user_id' => $attributes['user_id']]),
            'title' => fake()->randomElement(['Laravel Developer', 'PHP Engineer', 'Full-stack Developer (Vue)', 'Python Backend Developer']),
            'url' => fake()->optional()->url(),
            'location' => fake()->optional()->city(),
            'work_mode' => fake()->optional()->randomElement(WorkMode::cases()),
            'salary_min' => $salaryMin ? $salaryMin * 100 : null,
            'salary_max' => $salaryMin ? ($salaryMin + 10) * 100 : null,
            'salary_currency' => $salaryMin ? 'USD' : null,
            'source' => fake()->optional()->randomElement(['djinni', 'dou', 'linkedin', 'jobs.cz']),
            'description' => fake()->optional()->paragraphs(3, true),
        ];
    }
}
