<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningEvaluationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ListeningAttemptEvaluation extends Model
{
    protected $fillable = [
        'listening_attempt_id',
        'listening_test_id',
        'user_id',
        'evaluation_version',
        'status',
        'raw_score',
        'total_questions',
        'total_correct',
        'band_score',
        'evaluated_by',
        'evaluation_type',
        'summary',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListeningEvaluationStatus::class,
            'evaluation_type' => ListeningEvaluationType::class,
            'raw_score' => 'decimal:2',
            'total_questions' => 'integer',
            'total_correct' => 'decimal:2',
            'band_score' => 'decimal:1',
            'summary' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function answerEvaluations(): HasMany
    {
        return $this->hasMany(ListeningAttemptAnswerEvaluation::class);
    }

    public function latestAnswerEvaluation(): HasOne
    {
        return $this->hasOne(ListeningAttemptAnswerEvaluation::class)->latestOfMany();
    }
}
