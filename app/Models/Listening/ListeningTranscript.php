<?php

declare(strict_types=1);

namespace App\Models\Listening;

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
        'transcript_text',
        'formatted_transcript',
        'timestamped_transcript',
        'language',
        'visibility',
        'is_official',
        'created_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'timestamped_transcript' => 'array',
            'visibility' => ListeningTranscriptVisibility::class,
            'is_official' => 'boolean',
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

    public function scopeVisibleForReview(Builder $query): Builder
    {
        return $query->where('visibility', ListeningTranscriptVisibility::ReviewVisible);
    }
}
