<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TouchesReadingTestCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingQuestion extends Model
{
    use TouchesReadingTestCache;
    protected $fillable = [
        'group_id',
        'question_number',
        'prompt',
        'paragraph_reference',
        'reference_start_offset',
        'reference_end_offset',
        'reference_paragraph',
        'reference_type',
        'reference_phrase',
        'reference_sentence',
        'explanation',
        'marks',
        'sort_order',
        'difficulty',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'reference_start_offset' => 'integer',
            'reference_end_offset' => 'integer',
            'marks' => 'decimal:2',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestionGroup::class, 'group_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ReadingQuestionOption::class, 'question_id')->orderBy('sort_order');
    }

    public function correctAnswers(): HasMany
    {
        return $this->hasMany(ReadingCorrectAnswer::class, 'question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ReadingAnswer::class, 'question_id');
    }

    public function attemptsAtCurrentQuestion(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class, 'current_question_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (ReadingQuestion $question): void {
            $question->answers()->delete();
        });
    }

    protected function touchReadingTestForCache(): void
    {
        $this->loadMissing('group.passage');
        $this->touchReadingTestById($this->group?->passage?->reading_test_id);
    }
}
