<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\Concerns\TouchesReadingTestCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingQuestionGroup extends Model
{
    use TouchesReadingTestCache;
    protected $fillable = [
        'passage_id',
        'title',
        'instruction',
        'question_type',
        'start_question',
        'end_question',
        'sort_order',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => OfficialReadingQuestionType::class,
            'start_question' => 'integer',
            'end_question' => 'integer',
            'sort_order' => 'integer',
            'status' => PassageStatus::class,
            'settings' => 'array',
        ];
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ReadingQuestion::class, 'group_id')->orderBy('sort_order');
    }

    public function groupOptions(): HasMany
    {
        return $this->hasMany(ReadingQuestionOption::class, 'group_id')->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function getQuestionsCountAttribute(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }

        return $this->questions()->count();
    }

    public function getExpectedQuestionsCountAttribute(): int
    {
        if ($this->start_question === null || $this->end_question === null) {
            return 0;
        }

        return max(0, (int) $this->end_question - (int) $this->start_question + 1);
    }

    public function getQuestionRangeLabelAttribute(): string
    {
        if ($this->start_question === null || $this->end_question === null) {
            return '—';
        }

        if ($this->start_question === $this->end_question) {
            return (string) $this->start_question;
        }

        return "{$this->start_question}–{$this->end_question}";
    }

    public function getQuestionCountLabelAttribute(): string
    {
        return $this->questions_count.' / '.$this->expected_questions_count;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? PassageStatus::Draft->label();
    }

    protected function touchReadingTestForCache(): void
    {
        $this->loadMissing('passage');
        $this->touchReadingTestById($this->passage?->reading_test_id);
    }
}
