<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningAudio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'listening_audios';

    protected $fillable = [
        'original_name',
        'stored_name',
        'disk',
        'path',
        'processed_path',
        'normalized_path',
        'url',
        'mime_type',
        'extension',
        'file_size',
        'duration_seconds',
        'bitrate',
        'sample_rate',
        'channels',
        'format',
        'waveform_path',
        'waveform_json_path',
        'preview_waveform_path',
        'peaks',
        'loudness_lufs',
        'peak_db',
        'silence_report',
        'processing_status',
        'validation_status',
        'validation_errors',
        'processing_started_at',
        'processing_finished_at',
        'processing_error',
        'retry_count',
        'last_processed_at',
        'pipeline_version',
        'processing_locked_at',
        'processing_lock_token',
        'checksum',
        'uploaded_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'bitrate' => 'integer',
            'sample_rate' => 'integer',
            'channels' => 'integer',
            'peaks' => 'array',
            'loudness_lufs' => 'float',
            'peak_db' => 'float',
            'silence_report' => 'array',
            'processing_started_at' => 'datetime',
            'processing_finished_at' => 'datetime',
            'last_processed_at' => 'datetime',
            'processing_locked_at' => 'datetime',
            'retry_count' => 'integer',
            'processing_status' => ListeningAudioProcessingStatus::class,
            'validation_status' => ListeningAudioValidationStatus::class,
            'validation_errors' => 'array',
            'meta' => 'array',
        ];
    }

    public function title(): ?string
    {
        return is_array($this->meta) ? ($this->meta['title'] ?? null) : null;
    }

    public function description(): ?string
    {
        return is_array($this->meta) ? ($this->meta['description'] ?? null) : null;
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ListeningSection::class, 'audio_id');
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(ListeningQuestionGroup::class, 'audio_id');
    }

    public function transcripts(): HasMany
    {
        return $this->hasMany(ListeningTranscript::class, 'listening_audio_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(ListeningAudioProcessingLog::class, 'listening_audio_id')
            ->orderBy('created_at');
    }

    public function pipelineCurrentStage(): ?string
    {
        $pipeline = is_array($this->meta['pipeline'] ?? null) ? $this->meta['pipeline'] : [];

        return is_string($pipeline['current_stage'] ?? null) ? $pipeline['current_stage'] : null;
    }

    public function pipelineHistory(): array
    {
        $pipeline = is_array($this->meta['pipeline'] ?? null) ? $this->meta['pipeline'] : [];

        return is_array($pipeline['history'] ?? null) ? $pipeline['history'] : [];
    }

    public function playablePath(): ?string
    {
        $audio = is_array($this->meta['audio'] ?? null) ? $this->meta['audio'] : [];

        if (is_string($audio['playable_path'] ?? null)) {
            return $audio['playable_path'];
        }

        return $this->normalized_path ?: $this->processed_path ?: $this->path;
    }

    public function isLocked(): bool
    {
        return $this->processing_locked_at !== null && $this->processing_lock_token !== null;
    }

    public function scopeProcessingQueue(Builder $query): Builder
    {
        return $query->whereIn('processing_status', [
            ListeningAudioProcessingStatus::Pending,
            ListeningAudioProcessingStatus::Processing,
        ]);
    }
}
