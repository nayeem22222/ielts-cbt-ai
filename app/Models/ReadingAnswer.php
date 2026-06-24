<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'answer_json',
        'flagged',
        'is_correct',
        'marks_awarded',
        'evaluated_at',
        'evaluation_json',
        'answered_at',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'answer_json' => 'array',
            'flagged' => 'boolean',
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
            'evaluated_at' => 'datetime',
            'evaluation_json' => 'array',
            'answered_at' => 'datetime',
            'state' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ReadingAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestion::class, 'question_id');
    }
}
