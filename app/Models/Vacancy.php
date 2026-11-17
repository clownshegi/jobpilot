<?php

namespace App\Models;

use App\Enums\WorkMode;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'url',
        'location',
        'work_mode',
        'salary_min',
        'salary_max',
        'salary_currency',
        'source',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'work_mode' => WorkMode::class,
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasOne<JobApplication, $this>
     */
    public function application(): HasOne
    {
        return $this->hasOne(JobApplication::class);
    }
}
