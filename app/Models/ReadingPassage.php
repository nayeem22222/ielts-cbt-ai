<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\PassageStatus;
use App\Support\Reading\ReadingPassageContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPassage extends Model
{
    protected $fillable = [
        'reading_test_id',
        'part_number',
        'title',
        'subtitle',
        'instruction',
        'start_question',
        'end_question',
        'content_html',
        'content_text',
        'status',
        'settings',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'part_number' => 'integer',
            'start_question' => 'integer',
            'end_question' => 'integer',
            'sort_order' => 'integer',
            'status' => PassageStatus::class,
            'settings' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ReadingTest::class, 'reading_test_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ReadingQuestionGroup::class, 'passage_id')->orderBy('sort_order');
    }

    public function attemptsAtCurrentPassage(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class, 'current_passage_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('part_number');
    }

    public function getQuestionsCountAttribute(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }

        return (int) ReadingQuestion::query()
            ->whereHas('group', fn (Builder $query) => $query->where('passage_id', $this->id))
            ->count();
    }

    public function getQuestionRangeLabelAttribute(): string
    {
        if ($this->start_question === null || $this->end_question === null) {
            return '—';
        }

        if ($this->start_question === $this->end_question) {
            return (string) $this->start_question;
        }

        return "{$this->start_question}-{$this->end_question}";
    }

    public function getAutoParagraphLabelsAttribute(): bool
    {
        return (bool) ($this->settings['auto_paragraph_labels'] ?? false);
    }

    public function renderedContentHtml(): string
    {
        $html = ReadingPassageContentRenderer::sanitizeReferenceMarkers($this->content_html ?? '');

        if (! $this->auto_paragraph_labels) {
            return $html;
        }

        return ReadingPassageContentRenderer::applyParagraphLabels($html);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? PassageStatus::Draft->label();
    }
}
