<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttachTranscriptToSectionAction
{
    public function __construct(
        private readonly ValidateSectionBelongsToTestAction $validateSectionBelongsToTest,
    ) {}

    /**
     * @return array{section: ListeningSection, audio_match: bool}
     */
    public function execute(
        ListeningTest $test,
        ListeningSection $section,
        ListeningTranscript $transcript,
        bool $forceAttach = false,
    ): array {
        $this->validateSectionBelongsToTest->execute($test, $section);

        $audioMatch = $this->audioMatches($section, $transcript);

        if (! $audioMatch && config('listening.transcript.strict_audio_match', true) && ! $forceAttach) {
            throw ValidationException::withMessages([
                'transcript_id' => 'Transcript audio does not match section audio.',
            ]);
        }

        return DB::transaction(function () use ($section, $transcript, $audioMatch): array {
            $section->update(['transcript_id' => $transcript->id]);
            $section->refresh()->load(['transcript', 'audio']);

            return [
                'section' => $section,
                'audio_match' => $audioMatch,
            ];
        });
    }

    private function audioMatches(ListeningSection $section, ListeningTranscript $transcript): bool
    {
        if ($section->audio_id === null || $transcript->listening_audio_id === null) {
            return true;
        }

        return (int) $section->audio_id === (int) $transcript->listening_audio_id;
    }
}
