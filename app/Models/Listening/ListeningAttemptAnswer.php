<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningAnswerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ListeningAttemptAnswer extends Model
{
    protected $fillable = [
        'listening_attempt_id',
        'listening_test_id',
        'listening_question_id',
        'question_number',
        'student_answer',
        'normalized_answer',
        'correct_answer_snapshot',
        'is_correct',
        'marks_awarded',
        'answer_status',
        'answered_at',
        'time_spent_seconds',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'student_answer' => 'array',
            'normalized_answer' => 'array',
            'correct_answer_snapshot' => 'array',
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
            'answer_status' => ListeningAnswerStatus::class,
            'answered_at' => 'datetime',
            'time_spent_seconds' => 'integer',
            'meta' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ListeningAttempt::class, 'listening_attempt_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestion::class, 'listening_question_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ListeningAttemptAnswerEvaluation::class);
    }

    public function latestEvaluation(): HasOne
    {
        return $this->hasOne(ListeningAttemptAnswerEvaluation::class)->latestOfMany();
    }
}
