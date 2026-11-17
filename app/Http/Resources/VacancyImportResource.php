<?php

namespace App\Http\Resources;

use App\Models\VacancyImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VacancyImport
 */
class VacancyImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'status' => $this->status->value,
            'vacancy_id' => $this->vacancy_id,
            'message' => $this->message,
        ];
    }
}
