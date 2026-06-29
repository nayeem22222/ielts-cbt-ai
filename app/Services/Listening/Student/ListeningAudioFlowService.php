<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\ListeningAudioFlowStateData;
use App\Enums\Listening\ListeningAttemptPhase;
use App\Models\Listening\ListeningAttempt;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Validation\ValidationException;

class ListeningAudioFlowService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningOfficialFlowService $flow,
        private readonly ListeningOfficialTimerService $timer,
    ) {}

    public function markAudioStarted(ListeningAttempt $attempt, int $sectionNumber): ListeningAudioFlowStateData
    {
        if (! $this->canStartSectionAudio($attempt, $sectionNumber)) {
            throw ValidationException::withMessages([
                'section' => 'Audio cannot be started for this section in the current phase.',
            ]);
        }

        $meta = $this->audioMeta($attempt);
        $key = $this->sectionKey($sectionNumber);
        $section = is_array($meta['audio_flow'][$key] ?? null) ? $meta['audio_flow'][$key] : [];

        $meta['audio_flow'][$key] = array_merge($section, [
            'started_at' => $section['started_at'] ?? now()->toIso8601String(),
            'play_count' => (int) ($section['play_count'] ?? 0) + 1,
            'completed' => false,
            'last_position' => 0,
        ]);

        $this->attempts->update($attempt, ['timer_meta' => $meta]);

        return $this->buildState($attempt->refresh(), $sectionNumber);
    }

    public function markAudioEnded(ListeningAttempt $attempt, int $sectionNumber): ListeningAudioFlowStateData
    {
        $meta = $this->audioMeta($attempt);
        $key = $this->sectionKey($sectionNumber);
        $section = is_array($meta['audio_flow'][$key] ?? null) ? $meta['audio_flow'][$key] : [];

        $meta['audio_flow'][$key] = array_merge($section, [
            'ended_at' => now()->toIso8601String(),
            'completed' => true,
            'last_position' => $section['last_position'] ?? 0,
        ]);

        $this->attempts->update($attempt, ['timer_meta' => $meta]);

        return $this->buildState($attempt->refresh(), $sectionNumber);
    }

    public function canStartSectionAudio(ListeningAttempt $attempt, int $sectionNumber): bool
    {
        if ($sectionNumber < 1 || $sectionNumber > 4) {
            return false;
        }

        if (! $this->flow->canPlayAudio($attempt)) {
            return false;
        }

        if ($this->canReplaySectionAudio($attempt, $sectionNumber)) {
            return true;
        }

        $section = $this->sectionState($attempt, $sectionNumber);

        return (int) ($section['play_count'] ?? 0) < 1;
    }

    public function canReplaySectionAudio(ListeningAttempt $attempt, int $sectionNumber): bool
    {
        if (config('listening.official_audio.disable_replay', true)) {
            return false;
        }

        return $this->flow->canPlayAudio($attempt) && $this->timer->calculatePhase($attempt) === ListeningAttemptPhase::Listening;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAudioState(ListeningAttempt $attempt): array
    {
        $meta = $this->audioMeta($attempt);

        return is_array($meta['audio_flow'] ?? null) ? $meta['audio_flow'] : [];
    }

    public function updateAudioPosition(ListeningAttempt $attempt, int $sectionNumber, float $position): void
    {
        $meta = $this->audioMeta($attempt);
        $key = $this->sectionKey($sectionNumber);
        $section = is_array($meta['audio_flow'][$key] ?? null) ? $meta['audio_flow'][$key] : [];

        $meta['audio_flow'][$key] = array_merge($section, [
            'last_position' => max(0, $position),
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->attempts->update($attempt, ['timer_meta' => $meta]);
    }

    private function buildState(ListeningAttempt $attempt, int $sectionNumber): ListeningAudioFlowStateData
    {
        $section = $this->sectionState($attempt, $sectionNumber);

        return new ListeningAudioFlowStateData(
            attemptId: (int) $attempt->id,
            sectionNumber: $sectionNumber,
            canStart: $this->canStartSectionAudio($attempt, $sectionNumber),
            canReplay: $this->canReplaySectionAudio($attempt, $sectionNumber),
            completed: (bool) ($section['completed'] ?? false),
            playCount: (int) ($section['play_count'] ?? 0),
            sections: $this->getAudioState($attempt),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionState(ListeningAttempt $attempt, int $sectionNumber): array
    {
        $flow = $this->getAudioState($attempt);
        $section = $flow[$this->sectionKey($sectionNumber)] ?? [];

        return is_array($section) ? $section : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function audioMeta(ListeningAttempt $attempt): array
    {
        $meta = is_array($attempt->timer_meta) ? $attempt->timer_meta : [];
        $meta['audio_flow'] = is_array($meta['audio_flow'] ?? null) ? $meta['audio_flow'] : [];

        return $meta;
    }

    private function sectionKey(int $sectionNumber): string
    {
        return 'section_'.$sectionNumber;
    }
}
