<?php

namespace App\Actions;

use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

class UpdateVacancy
{
    public function __construct(private ResolveCompany $resolveCompany) {}

    /**
     * @param  array<string, mixed>  $data  validated UpdateVacancyRequest data
     */
    public function handle(Vacancy $vacancy, array $data): Vacancy
    {
        return DB::transaction(function () use ($vacancy, $data) {
            $company = $this->resolveCompany->handle($vacancy->user, $data['company_name']);

            $vacancy->update([
                ...collect($data)->except(['company_name'])->all(),
                'company_id' => $company->id,
            ]);

            return $vacancy;
        });
    }
}
