<?php

namespace App\Http\Requests;

use App\Enums\WorkMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VacancyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['nullable', Rule::enum(WorkMode::class)],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => [
                'nullable', 'integer', 'min:0', 'max:10000000',
                Rule::when($this->filled('salary_min'), ['gte:salary_min']),
            ],
            'salary_currency' => ['nullable', 'required_with:salary_min,salary_max', 'string', 'size:3', 'alpha'],
            'source' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salary_currency.required_with' => 'Pick a currency for the salary.',
            'salary_max.gte' => 'The upper salary bound cannot be lower than the lower one.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company',
            'title' => 'position',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('salary_currency'))) {
            $this->merge(['salary_currency' => strtoupper($this->input('salary_currency'))]);
        }
    }
}
