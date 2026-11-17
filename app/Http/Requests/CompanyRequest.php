<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('companies')->where('user_id', $this->user()->id)->ignore($company),
            ],
            'website' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
