<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\AnswerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionCorrectAnswer extends Model
{
    protected $fillable = [
        'question_id',
        'answer_key',
        'answer_type',
        'answer_value',
        'answer_json',
    ];

    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'answer_json' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
