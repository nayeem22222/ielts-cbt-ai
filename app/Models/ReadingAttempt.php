<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\TestAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReadingAttempt extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'reading_test_id',
        'status',
        'started_at',
        'remaining_seconds',
        'current_passage_id',
        'current_question_id',
        'time_spent',
        'navigation_state',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => TestAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'remaining_seconds' => 'integer',
            'score' => 'decimal:2',
            'band' => 'decimal:1',
            'time_spent' => 'integer',
            'navigation_state' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReadingAttempt $attempt): void {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ReadingTest::class, 'reading_test_id');
    }

    public function currentPassage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'current_passage_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestion::class, 'current_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ReadingAnswer::class, 'attempt_id');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ReadingHighlight::class, 'attempt_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ReadingNote::class, 'attempt_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ReadingQuestionTicket::class, 'attempt_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
