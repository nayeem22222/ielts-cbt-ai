<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ReadingQuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultQuestionScore extends Model
{
    protected $fillable = [
        'result_id',
        'question_id',
        'student_answer_id',
        'test_section_id',
        'question_type',
        'question_number',
        'student_response',
        'expected_response',
        'is_correct',
        'score_awarded',
        'max_score',
        'partial_ratio',
        'feedback',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => ReadingQuestionType::class,
            'question_number' => 'integer',
            'is_correct' => 'boolean',
            'score_awarded' => 'decimal:2',
            'max_score' => 'decimal:2',
            'partial_ratio' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function studentAnswer(): BelongsTo
    {
        return $this->belongsTo(StudentAnswer::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }
}
