<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningQuestionGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listening_test_id',
        'listening_section_id',
        'title',
        'instruction',
        'question_type',
        'start_question_number',
        'end_question_number',
        'total_questions',
        'display_order',
        'layout_type',
        'audio_id',
        'transcript_reference',
        'image_path',
        'image_alt',
        'content',
        'options',
        'settings',
        'validation_rules',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => ListeningQuestionType::class,
            'layout_type' => ListeningLayoutType::class,
            'start_question_number' => 'integer',
            'end_question_number' => 'integer',
            'total_questions' => 'integer',
            'display_order' => 'integer',
            'transcript_reference' => 'array',
            'options' => 'array',
            'settings' => 'array',
            'validation_rules' => 'array',
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

    public function audio(): BelongsTo
    {
        return $this->belongsTo(ListeningAudio::class, 'audio_id');
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
        return $query->orderBy('display_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
