<?php

namespace App\Http\Requests;

use App\Support\UrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyImportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required', 'string', 'max:2048', 'url:http,https',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! app(UrlGuard::class)->isAllowed($value)) {
                        $fail('This link points to a private or unknown address.');
                    }
                },
            ],
        ];
    }
}
