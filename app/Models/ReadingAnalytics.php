<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReadingAnalytics extends Model
{
    protected $table = 'reading_analytics';

    protected $fillable = [
        'uuid',
        'test_attempt_id',
        'result_id',
        'test_id',
        'user_id',
        'band',
        'accuracy_percent',
        'average_time_seconds',
        'skipped_count',
        'total_questions',
        'time_per_question',
        'heat_map',
        'metadata',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'band' => 'decimal:1',
            'accuracy_percent' => 'decimal:2',
            'average_time_seconds' => 'integer',
            'skipped_count' => 'integer',
            'total_questions' => 'integer',
            'time_per_question' => 'array',
            'heat_map' => 'array',
            'metadata' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReadingAnalytics $analytics): void {
            if (empty($analytics->uuid)) {
                $analytics->uuid = (string) Str::uuid();
            }
        });
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ExamTest::class, 'test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
