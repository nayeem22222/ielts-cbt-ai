<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningTranscriptSourceType;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningTranscript extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listening_audio_id',
        'title',
        'passage_title',
        'transcript_text',
        'formatted_transcript',
        'passage_note',
        'timestamped_transcript',
        'language',
        'visibility',
        'is_official',
        'source_type',
        'version',
        'reviewed_at',
        'reviewed_by',
        'created_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'timestamped_transcript' => 'array',
            'visibility' => ListeningTranscriptVisibility::class,
            'source_type' => ListeningTranscriptSourceType::class,
            'is_official' => 'boolean',
            'version' => 'integer',
            'reviewed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(ListeningAudio::class, 'listening_audio_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ListeningSection::class, 'transcript_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleForReview(Builder $query): Builder
    {
        return $query->where('visibility', ListeningTranscriptVisibility::ReviewVisible);
    }
}
