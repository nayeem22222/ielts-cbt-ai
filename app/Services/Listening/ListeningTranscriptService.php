<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Actions\Listening\AttachTranscriptToSectionAction;
use App\Actions\Listening\DetachTranscriptFromSectionAction;
use App\Actions\Listening\NormalizeTranscriptTextAction;
use App\Actions\Listening\ValidateTimestampedTranscriptAction;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use App\Repositories\Listening\ListeningTranscriptRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningTranscriptService
{
    public function __construct(
        private readonly ListeningTranscriptRepository $transcripts,
        private readonly NormalizeTranscriptTextAction $normalizeText,
        private readonly ValidateTimestampedTranscriptAction $validateTimestamps,
        private readonly AttachTranscriptToSectionAction $attachToSection,
        private readonly DetachTranscriptFromSectionAction $detachFromSection,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transcripts->paginateForAdmin($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningTranscript
    {
        return DB::transaction(function () use ($data): ListeningTranscript {
            $payload = $this->preparePayload($data);

            if (! empty($payload['timestamped_transcript'])) {
                $this->assertValidTimestamps($payload['timestamped_transcript'], $payload);
            }

            return $this->transcripts->create($payload)->load(['audio', 'createdBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningTranscript $transcript, array $data): ListeningTranscript
    {
        return DB::transaction(function () use ($transcript, $data): ListeningTranscript {
            $payload = $this->preparePayload($data, $transcript);

            if (array_key_exists('timestamped_transcript', $payload) && $payload['timestamped_transcript'] !== null) {
                $this->assertValidTimestamps($payload['timestamped_transcript'], $payload, $transcript);
            }

            return $this->transcripts->update($transcript, $payload)->load(['audio', 'createdBy']);
        });
    }

    public function delete(ListeningTranscript $transcript): bool
    {
        return DB::transaction(fn (): bool => $this->transcripts->delete($transcript));
    }

    /**
     * @param  array<int, array<string, mixed>>  $timestampedData
     */
    public function updateTimestampedTranscript(ListeningTranscript $transcript, array $timestampedData): ListeningTranscript
    {
        return DB::transaction(function () use ($transcript, $timestampedData): ListeningTranscript {
            $this->assertValidTimestamps($timestampedData, [
                'transcript_text' => $transcript->transcript_text,
                'listening_audio_id' => $transcript->listening_audio_id,
            ], $transcript);

            return $this->transcripts->update($transcript, [
                'timestamped_transcript' => $timestampedData,
                'version' => (int) $transcript->version + 1,
            ])->load(['audio', 'createdBy']);
        });
    }

    /**
     * @return array{section: ListeningSection, audio_match: bool}
     */
    public function attachToSection(
        ListeningTest $test,
        ListeningSection $section,
        ListeningTranscript $transcript,
        bool $forceAttach = false,
    ): array {
        return $this->attachToSection->execute($test, $section, $transcript, $forceAttach);
    }

    public function detachFromSection(ListeningTest $test, ListeningSection $section): ListeningSection
    {
        return $this->detachFromSection->execute($test, $section);
    }

    public function normalizeTranscriptText(string $text): string
    {
        return $this->normalizeText->execute($text);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTranscriptReadiness(ListeningTranscript $transcript): array
    {
        $hasPlain = trim((string) $transcript->transcript_text) !== '';
        $hasFormatted = trim((string) ($transcript->formatted_transcript ?? '')) !== '';
        $timestamped = $transcript->timestamped_transcript ?? [];
        $hasTimestamped = is_array($timestamped) && $timestamped !== [];
        $lineCount = $hasTimestamped ? count($timestamped) : 0;

        return [
            'has_plain_text' => $hasPlain,
            'has_formatted_transcript' => $hasFormatted,
            'has_timestamped_transcript' => $hasTimestamped,
            'timestamp_line_count' => $lineCount,
            'visibility' => $transcript->visibility?->value,
            'audio_linked' => $transcript->listening_audio_id !== null,
            'ready_for_review' => $hasPlain && $transcript->visibility === ListeningTranscriptVisibility::ReviewVisible,
            'ready_for_audio_sync' => $hasTimestamped && $transcript->listening_audio_id !== null,
            'ready_for_question_builder' => $hasPlain,
        ];
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function getAvailableForSection(?int $audioId = null): Collection
    {
        return $this->transcripts->getAvailableForSection($audioId);
    }

    public function audioMatchesSection(ListeningSection $section, ListeningTranscript $transcript): bool
    {
        if ($section->audio_id === null || $transcript->listening_audio_id === null) {
            return true;
        }

        return (int) $section->audio_id === (int) $transcript->listening_audio_id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, ?ListeningTranscript $existing = null): array
    {
        $maxLength = (int) config('listening.transcript.max_text_length', 100000);

        if (isset($data['transcript_text'])) {
            $data['transcript_text'] = $this->normalizeText->execute((string) $data['transcript_text']);

            if (mb_strlen($data['transcript_text']) > $maxLength) {
                throw ValidationException::withMessages([
                    'transcript_text' => "Transcript text cannot exceed {$maxLength} characters.",
                ]);
            }
        }

        if (isset($data['formatted_transcript']) && $data['formatted_transcript'] !== null) {
            $data['formatted_transcript'] = trim((string) $data['formatted_transcript']);
        }

        if (array_key_exists('listening_audio_id', $data) && ($data['listening_audio_id'] === '' || $data['listening_audio_id'] === null)) {
            $data['listening_audio_id'] = null;
        }

        if (! isset($data['visibility']) && $existing === null) {
            $data['visibility'] = ListeningTranscriptVisibility::AdminOnly;
        }

        if (! isset($data['source_type']) && $existing === null) {
            $data['source_type'] = 'manual';
        }

        if (isset($data['source_type']) && $data['source_type'] === 'ai_generated' && ! config('listening.transcript.allow_ai_generated', false)) {
            throw ValidationException::withMessages([
                'source_type' => 'AI generated transcripts are not enabled.',
            ]);
        }

        if (isset($data['timestamped_transcript']) && $data['timestamped_transcript'] === '') {
            $data['timestamped_transcript'] = null;
        }

        if (isset($data['timestamped_transcript']) && is_string($data['timestamped_transcript'])) {
            $decoded = json_decode($data['timestamped_transcript'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'timestamped_transcript' => 'Invalid JSON for timestamped transcript.',
                ]);
            }

            $data['timestamped_transcript'] = $decoded;
        }

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $context
     */
    private function assertValidTimestamps(array $lines, array $context, ?ListeningTranscript $transcript = null): void
    {
        $audioId = $context['listening_audio_id'] ?? $transcript?->listening_audio_id;
        $duration = null;

        if ($audioId !== null) {
            $audio = \App\Models\Listening\ListeningAudio::query()->find($audioId);
            $duration = $audio?->duration_seconds !== null ? (float) $audio->duration_seconds : null;
        }

        $errors = $this->validateTimestamps->execute($lines, $duration);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'timestamped_transcript' => $errors[0],
            ]);
        }
    }
}
