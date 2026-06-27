<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningSectionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listening_test_id',
        'section_number',
        'title',
        'instruction',
        'section_type',
        'audio_id',
        'transcript_id',
        'total_questions',
        'start_question_number',
        'end_question_number',
        'display_order',
        'duration_seconds',
        'preparation_seconds',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'section_number' => 'integer',
            'section_type' => ListeningSectionType::class,
            'total_questions' => 'integer',
            'start_question_number' => 'integer',
            'end_question_number' => 'integer',
            'display_order' => 'integer',
            'duration_seconds' => 'integer',
            'preparation_seconds' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(ListeningAudio::class, 'audio_id');
    }

    public function transcript(): BelongsTo
    {
        return $this->belongsTo(ListeningTranscript::class, 'transcript_id');
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(ListeningQuestionGroup::class)->orderBy('display_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ListeningQuestion::class)->orderBy('question_number');
    }

    public function markers(): HasMany
    {
        return $this->hasMany(ListeningQuestionMarker::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('section_number');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
