<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningResultStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listening_attempt_id',
        'listening_attempt_evaluation_id',
        'listening_test_id',
        'user_id',
        'result_code',
        'status',
        'raw_score',
        'total_questions',
        'total_correct',
        'total_incorrect',
        'total_unanswered',
        'band_score',
        'listening_duration_seconds',
        'submitted_at',
        'evaluated_at',
        'published_at',
        'is_visible_to_student',
        'section_breakdown',
        'question_type_breakdown',
        'question_summary',
        'result_snapshot',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListeningResultStatus::class,
            'raw_score' => 'decimal:2',
            'total_questions' => 'integer',
            'total_correct' => 'decimal:2',
            'total_incorrect' => 'decimal:2',
            'total_unanswered' => 'integer',
            'band_score' => 'decimal:1',
            'listening_duration_seconds' => 'integer',
            'submitted_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'published_at' => 'datetime',
            'is_visible_to_student' => 'boolean',
            'section_breakdown' => 'array',
            'question_type_breakdown' => 'array',
            'question_summary' => 'array',
            'result_snapshot' => 'array',
            'meta' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ListeningAttempt::class, 'listening_attempt_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ListeningAttemptEvaluation::class, 'listening_attempt_evaluation_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(ListeningReviewItem::class, 'listening_result_id')->orderBy('question_number');
    }
}
