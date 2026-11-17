<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * My application for a vacancy: where it sits in the pipeline (status + position on the board).
 */
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'position',
        'applied_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'position' => 'integer',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return HasMany<ApplicationStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(ApplicationStatusChange::class);
    }

    /**
     * @param  Builder<JobApplication>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->whereRelation('vacancy', 'user_id', $user->id);
    }
}
