<?php

namespace App\Http\Resources;

use App\Models\ApplicationStatusChange;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'position' => $this->position,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'notes' => $this->notes,
            'vacancy' => VacancyResource::make($this->whenLoaded('vacancy')),
            'status_changes' => $this->whenLoaded('statusChanges', fn () => $this->statusChanges->map(
                fn (ApplicationStatusChange $change) => [
                    'id' => $change->id,
                    'from_label' => $change->from_status?->label(),
                    'to_label' => $change->to_status->label(),
                    'created_at' => $change->created_at?->toIso8601String(),
                ],
            )),
        ];
    }
}
