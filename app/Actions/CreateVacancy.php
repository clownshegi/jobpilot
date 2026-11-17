<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Events\BoardChanged;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

/**
 * Creates a vacancy together with its application, which goes to the end of its board column.
 */
class CreateVacancy
{
    public function __construct(private ResolveCompany $resolveCompany) {}

    /**
     * @param  array<string, mixed>  $data  validated StoreVacancyRequest data
     */
    public function handle(User $user, array $data): Vacancy
    {
        return DB::transaction(function () use ($user, $data) {
            $company = $this->resolveCompany->handle($user, $data['company_name']);
            $status = ApplicationStatus::from($data['status']);

            $vacancy = $user->vacancies()->create([
                ...collect($data)->except(['company_name', 'status', 'notes'])->all(),
                'company_id' => $company->id,
            ]);

            $application = $vacancy->application()->create([
                'status' => $status,
                'position' => $user->jobApplications()->where('status', $status)->count(),
                'applied_at' => $status === ApplicationStatus::Wishlist ? null : now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $application->statusChanges()->create(['from_status' => null, 'to_status' => $status]);

            BoardChanged::dispatch($user->id);

            return $vacancy;
        });
    }
}
