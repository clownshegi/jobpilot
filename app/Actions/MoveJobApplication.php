<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Events\BoardChanged;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves an application to a board column (status) and position, renumbering both columns.
 * A status change is recorded in the history, and the first move out of the wishlist sets applied_at.
 */
class MoveJobApplication
{
    /**
     * @param  int|null  $position  zero-based index in the target column; null puts it at the end
     */
    public function handle(JobApplication $application, ApplicationStatus $status, ?int $position = null): JobApplication
    {
        return DB::transaction(function () use ($application, $status, $position) {
            $user = $application->vacancy->user;
            $fromStatus = $application->status;

            $siblingIds = $this->columnIds($user, $status, except: $application->id);
            $position = $position === null ? count($siblingIds) : max(0, min($position, count($siblingIds)));
            array_splice($siblingIds, $position, 0, [$application->id]);

            if ($fromStatus !== $status) {
                $application->status = $status;
                $application->applied_at ??= $status === ApplicationStatus::Wishlist ? null : now();
                $application->save();

                $application->statusChanges()->create(['from_status' => $fromStatus, 'to_status' => $status]);

                $this->renumber($this->columnIds($user, $fromStatus));
            }

            $this->renumber($siblingIds);

            BoardChanged::dispatch($user->id);

            return $application->refresh();
        });
    }

    /**
     * @return list<int>
     */
    private function columnIds(User $user, ApplicationStatus $status, ?int $except = null): array
    {
        return JobApplication::query()
            ->ownedBy($user)
            ->where('status', $status)
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->orderBy('position')
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $ids  application ids in their new order
     */
    private function renumber(array $ids): void
    {
        foreach ($ids as $position => $id) {
            JobApplication::whereKey($id)->where('position', '!=', $position)->update(['position' => $position]);
        }
    }
}
