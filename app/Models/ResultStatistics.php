<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultStatistics extends Model
{
    protected $table = 'result_statistics';

    protected $fillable = [
        'result_id',
        'total_questions',
        'answered_count',
        'correct_count',
        'incorrect_count',
        'unanswered_count',
        'flagged_count',
        'partial_count',
        'raw_score',
        'max_score',
        'accuracy_percent',
        'by_question_type',
        'by_passage',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'answered_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'unanswered_count' => 'integer',
            'flagged_count' => 'integer',
            'partial_count' => 'integer',
            'raw_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'accuracy_percent' => 'decimal:2',
            'by_question_type' => 'array',
            'by_passage' => 'array',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
