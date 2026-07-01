<?php

declare(strict_types=1);

namespace App\Models\Listening;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningReviewItem extends Model
{
    protected $fillable = [
        'listening_result_id',
        'listening_attempt_id',
        'listening_attempt_evaluation_id',
        'listening_attempt_answer_evaluation_id',
        'listening_question_id',
        'listening_section_id',
        'listening_transcript_id',
        'question_number',
        'section_number',
        'question_type',
        'student_answer_snapshot',
        'correct_answer_snapshot',
        'accepted_answers_snapshot',
        'normalized_answer_snapshot',
        'match_status',
        'match_reason',
        'marks_awarded',
        'marks_available',
        'transcript_line_start',
        'transcript_line_end',
        'transcript_text_snippet',
        'highlighted_transcript',
        'audio_timestamp_start',
        'audio_timestamp_end',
        'explanation',
        'visibility_meta',
        'admin_meta',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'section_number' => 'integer',
            'student_answer_snapshot' => 'array',
            'correct_answer_snapshot' => 'array',
            'accepted_answers_snapshot' => 'array',
            'normalized_answer_snapshot' => 'array',
            'marks_awarded' => 'decimal:2',
            'marks_available' => 'decimal:2',
            'transcript_line_start' => 'integer',
            'transcript_line_end' => 'integer',
            'highlighted_transcript' => 'array',
            'audio_timestamp_start' => 'decimal:2',
            'audio_timestamp_end' => 'decimal:2',
            'visibility_meta' => 'array',
            'admin_meta' => 'array',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(ListeningResult::class, 'listening_result_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ListeningAttempt::class, 'listening_attempt_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ListeningAttemptEvaluation::class, 'listening_attempt_evaluation_id');
    }

    public function answerEvaluation(): BelongsTo
    {
        return $this->belongsTo(ListeningAttemptAnswerEvaluation::class, 'listening_attempt_answer_evaluation_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestion::class, 'listening_question_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ListeningSection::class, 'listening_section_id');
    }

    public function transcript(): BelongsTo
    {
        return $this->belongsTo(ListeningTranscript::class, 'listening_transcript_id');
    }
}
