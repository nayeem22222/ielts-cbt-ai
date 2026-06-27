<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningAttempt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'listening_test_id',
        'status',
        'started_at',
        'submitted_at',
        'expires_at',
        'total_questions',
        'total_answered',
        'total_correct',
        'raw_score',
        'band_score',
        'duration_seconds',
        'remaining_seconds',
        'current_section_number',
        'current_question_number',
        'browser_info',
        'ip_address',
        'device_info',
        'security_flags',
        'result_meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListeningAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'total_questions' => 'integer',
            'total_answered' => 'integer',
            'total_correct' => 'integer',
            'raw_score' => 'integer',
            'band_score' => 'decimal:1',
            'duration_seconds' => 'integer',
            'remaining_seconds' => 'integer',
            'current_section_number' => 'integer',
            'current_question_number' => 'integer',
            'browser_info' => 'array',
            'device_info' => 'array',
            'security_flags' => 'array',
            'result_meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ListeningAttemptAnswer::class);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', ListeningAttemptStatus::InProgress);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
