<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Jobs\Listening\GenerateListeningWaveformJob;
use App\Jobs\Listening\ProcessListeningAudioJob;
use App\Models\Listening\ListeningAudio;
use App\Repositories\Listening\ListeningAudioRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningAudioService
{
    public function __construct(
        private readonly ListeningAudioRepository $audios,
        private readonly ListeningAudioUploadService $uploads,
        private readonly ListeningAudioStorageService $storage,
        private readonly ListeningAudioValidationService $validation,
        private readonly ListeningWaveformService $waveforms,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return $this->audios->paginateForAdmin($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromUpload(UploadedFile $file, array $data, ?int $uploadedBy = null): ListeningAudio
    {
        $validation = $this->validation->validateFile($file);

        if (! $validation->isValid()) {
            throw ValidationException::withMessages([
                'audio_file' => $validation->errors()[0]['message'] ?? 'Invalid audio file.',
            ]);
        }

        return DB::transaction(function () use ($file, $data, $uploadedBy): ListeningAudio {
            $stored = $this->uploads->storeOriginal($file);
            $existing = $this->audios->findByChecksum((string) $stored['checksum']);

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'audio_file' => 'This audio file has already been uploaded.',
                ]);
            }

            $audio = $this->audios->create(
                $this->uploads->buildAudioRecordPayload($stored, $data, $uploadedBy),
            );

            $this->dispatchProcessing($audio);

            return $audio->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningAudio $audio, array $data): ListeningAudio
    {
        return DB::transaction(function () use ($audio, $data): ListeningAudio {
            $meta = is_array($audio->meta) ? $audio->meta : [];

            if (array_key_exists('title', $data)) {
                $meta['title'] = $data['title'];
            }

            if (array_key_exists('description', $data)) {
                $meta['description'] = $data['description'];
            }

            if (array_key_exists('meta', $data) && is_array($data['meta'])) {
                $meta = array_merge($meta, $data['meta']);
            }

            return $this->audios->update($audio, [
                'meta' => $meta !== [] ? $meta : null,
            ]);
        });
    }

    public function delete(ListeningAudio $audio): bool
    {
        $usage = $this->getSectionUsage($audio);

        if ($usage['sections']->isNotEmpty() || $usage['question_groups']->isNotEmpty() || $usage['transcripts']->isNotEmpty()) {
            throw ValidationException::withMessages([
                'audio' => 'Audio cannot be deleted because it is used by a section, question group, or transcript.',
            ]);
        }

        return DB::transaction(function () use ($audio): bool {
            $deleted = $this->audios->delete($audio);

            if ($deleted) {
                $this->storage->deleteFiles($audio);
                $this->waveforms->deleteWaveformFiles($audio);
            }

            return $deleted;
        });
    }

    public function dispatchProcessing(ListeningAudio $audio): void
    {
        ProcessListeningAudioJob::dispatch($audio->id)->onQueue((string) config('listening.audio.queue', 'default'));
    }

    public function retryProcessing(ListeningAudio $audio, bool $force = false): void
    {
        $limit = (int) config('listening.audio.retry_limit', 3);

        if (! $force && (int) $audio->retry_count >= $limit) {
            throw ValidationException::withMessages([
                'retry' => 'Retry limit exceeded.',
            ]);
        }

        DB::transaction(function () use ($audio, $force): void {
            $this->audios->update($audio, [
                'processing_status' => ListeningAudioProcessingStatus::Pending,
                'validation_status' => ListeningAudioValidationStatus::Pending,
                'validation_errors' => null,
                'processing_error' => null,
                'processing_started_at' => null,
                'processing_finished_at' => null,
                'retry_count' => $force ? (int) $audio->retry_count : ((int) $audio->retry_count + 1),
            ]);
        });

        $this->dispatchProcessing($audio->refresh());
    }

    public function dispatchWaveformGeneration(ListeningAudio $audio): void
    {
        GenerateListeningWaveformJob::dispatch($audio->id)->onQueue((string) config('listening.audio.queue', 'default'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAudioReadiness(ListeningAudio $audio): array
    {
        $missing = [];

        if (! $this->storage->exists($audio)) {
            $missing[] = 'Processed audio file is missing.';
        }

        if ($audio->processing_status !== ListeningAudioProcessingStatus::Completed) {
            $missing[] = 'Audio is not processed.';
        }

        if ($audio->validation_status !== ListeningAudioValidationStatus::Valid) {
            $missing[] = 'Audio is invalid.';
        }

        if ($audio->duration_seconds === null) {
            $missing[] = 'Audio duration is missing.';
        }

        if (blank($audio->waveform_json_path)) {
            $missing[] = 'Audio waveform is missing.';
        }

        return [
            'processing_status' => $audio->processing_status?->value,
            'validation_status' => $audio->validation_status?->value,
            'audio_processing_completed' => $audio->processing_status === ListeningAudioProcessingStatus::Completed,
            'audio_validation_valid' => $audio->validation_status === ListeningAudioValidationStatus::Valid,
            'waveform_available' => filled($audio->waveform_json_path),
            'duration_available' => $audio->duration_seconds !== null,
            'is_ready' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return array{
     *     sections: \Illuminate\Support\Collection,
     *     question_groups: \Illuminate\Support\Collection,
     *     transcripts: \Illuminate\Support\Collection
     * }
     */
    public function getSectionUsage(ListeningAudio $audio): array
    {
        $audio->loadMissing(['sections.test', 'questionGroups', 'transcripts']);

        return [
            'sections' => $audio->sections,
            'question_groups' => $audio->questionGroups,
            'transcripts' => $audio->transcripts,
        ];
    }

    /**
     * @return Collection<int, ListeningAudio>
     */
    public function selectableForSections(bool $includeAll = false): \Illuminate\Database\Eloquent\Collection
    {
        return $this->audios->selectableForSections($includeAll);
    }
}
