<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningQuestionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listening_test_id',
        'listening_section_id',
        'listening_question_group_id',
        'question_number',
        'question_type',
        'question_text',
        'question_html',
        'instruction',
        'options',
        'correct_answer',
        'accepted_answers',
        'answer_format',
        'word_limit',
        'case_sensitive',
        'order_sensitive',
        'allow_plural',
        'allow_articles',
        'allow_punctuation_variation',
        'marks',
        'explanation',
        'transcript_location',
        'audio_timestamp_start',
        'audio_timestamp_end',
        'display_order',
        'is_required',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'question_type' => ListeningQuestionType::class,
            'options' => 'array',
            'correct_answer' => 'array',
            'accepted_answers' => 'array',
            'answer_format' => ListeningAnswerFormat::class,
            'word_limit' => 'integer',
            'case_sensitive' => 'boolean',
            'order_sensitive' => 'boolean',
            'allow_plural' => 'boolean',
            'allow_articles' => 'boolean',
            'allow_punctuation_variation' => 'boolean',
            'marks' => 'decimal:2',
            'transcript_location' => 'array',
            'audio_timestamp_start' => 'decimal:3',
            'audio_timestamp_end' => 'decimal:3',
            'display_order' => 'integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ListeningSection::class, 'listening_section_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestionGroup::class, 'listening_question_group_id');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(ListeningAttemptAnswer::class);
    }

    public function markers(): HasMany
    {
        return $this->hasMany(ListeningQuestionMarker::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('question_number');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
