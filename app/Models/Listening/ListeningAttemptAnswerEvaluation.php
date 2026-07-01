<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningMatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ListeningAttemptAnswerEvaluation extends Model
{
    protected $fillable = [
        'listening_attempt_evaluation_id',
        'listening_attempt_answer_id',
        'listening_attempt_id',
        'listening_question_id',
        'question_number',
        'question_type',
        'student_answer_snapshot',
        'normalized_student_answer',
        'correct_answer_snapshot',
        'accepted_answers_snapshot',
        'matched_answer',
        'is_correct',
        'marks_available',
        'marks_awarded',
        'match_status',
        'match_reason',
        'normalization_steps',
        'evaluator_meta',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'student_answer_snapshot' => 'array',
            'normalized_student_answer' => 'array',
            'correct_answer_snapshot' => 'array',
            'accepted_answers_snapshot' => 'array',
            'matched_answer' => 'array',
            'is_correct' => 'boolean',
            'marks_available' => 'decimal:2',
            'marks_awarded' => 'decimal:2',
            'match_status' => ListeningMatchStatus::class,
            'normalization_steps' => 'array',
            'evaluator_meta' => 'array',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ListeningAttemptEvaluation::class, 'listening_attempt_evaluation_id');
    }

    public function attemptAnswer(): BelongsTo
    {
        return $this->belongsTo(ListeningAttemptAnswer::class, 'listening_attempt_answer_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ListeningAttempt::class, 'listening_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestion::class, 'listening_question_id');
    }

    public function latestForAnswer(): HasOne
    {
        return $this->hasOne(self::class, 'listening_attempt_answer_id')->latestOfMany();
    }
}
