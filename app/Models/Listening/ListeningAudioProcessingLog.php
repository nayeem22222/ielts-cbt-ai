<?php

declare(strict_types=1);

namespace App\Models\Listening;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningAudioProcessingLog extends Model
{
    protected $table = 'listening_audio_processing_logs';

    protected $fillable = [
        'listening_audio_id',
        'job_id',
        'stage',
        'status',
        'message',
        'context',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(ListeningAudio::class, 'listening_audio_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function durationForHumans(): string
    {
        if ($this->duration_ms === null) {
            return '—';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms.'ms';
        }

        return round($this->duration_ms / 1000, 2).'s';
    }
}
