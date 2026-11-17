<?php

namespace App\Events;

use App\Http\Resources\VacancyImportResource;
use App\Models\VacancyImport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the user's open tabs how their import is going (processing → done / failed).
 */
class VacancyImportUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public VacancyImport $import) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->import->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'vacancy-import.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return VacancyImportResource::make($this->import)->resolve();
    }
}
