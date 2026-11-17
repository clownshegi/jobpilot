<?php

namespace App\Http\Resources;

use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vacancy
 */
class VacancyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'location' => $this->location,
            'work_mode' => $this->work_mode?->value,
            'work_mode_label' => $this->work_mode?->label(),
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'source' => $this->source,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'application' => JobApplicationResource::make($this->whenLoaded('application')),
        ];
    }
}
