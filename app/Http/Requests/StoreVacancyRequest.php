<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Validation\Rule;

/**
 * Same fields as VacancyRequest, plus the initial application status and notes.
 */
class StoreVacancyRequest extends VacancyRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
