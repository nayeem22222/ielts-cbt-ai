<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ResultStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Result extends Model
{
    protected $fillable = [
        'uuid',
        'test_attempt_id',
        'overall_band',
        'raw_score',
        'max_score',
        'status',
        'computed_at',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'overall_band' => 'decimal:1',
            'raw_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'computed_at' => 'datetime',
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Result $result): void {
            if (empty($result->uuid)) {
                $result->uuid = (string) Str::uuid();
            }
        });
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function bandScores(): HasMany
    {
        return $this->hasMany(BandScore::class);
    }

    public function questionScores(): HasMany
    {
        return $this->hasMany(ResultQuestionScore::class);
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(ResultStatistics::class);
    }
}
