<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningNavigationStateService;
use App\Services\Listening\Student\ListeningPlayerRecoveryService;
use App\Services\Listening\Student\ListeningQuestionPaletteService;

class SyncListeningPlayerStateAction
{
    public function __construct(
        private readonly ListeningNavigationStateService $navigationState,
        private readonly ListeningQuestionPaletteService $palette,
        private readonly ListeningPlayerRecoveryService $recovery,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(ListeningAttempt $attempt, array $payload): array
    {
        $section = (int) ($payload['current_section_number'] ?? $attempt->current_section_number ?? 1);
        $question = (int) ($payload['current_question_number'] ?? $attempt->current_question_number ?? 1);

        $navigation = $this->navigationState->updateCurrentPosition($attempt, $section, $question);

        $state = [
            'current_section_number' => $section,
            'current_question_number' => $question,
            'visible_question_numbers' => $payload['visible_question_numbers'] ?? null,
            'audio_state' => $payload['audio_state'] ?? null,
            'synced_at' => now()->toIso8601String(),
        ];

        $this->navigationState->savePlayerState($attempt, $state);

        $recovery = null;

        if (isset($payload['client_draft']) && is_array($payload['client_draft'])) {
            $snapshot = $this->recovery->buildRecoveryPayload($attempt)['server_snapshot'] ?? [];
            $recovery = $this->recovery->detectUnsavedClientAnswers($payload['client_draft'], $snapshot)->toArray();
        }

        return [
            'success' => true,
            'navigation' => $navigation->toArray(),
            'palette' => $this->palette->build($attempt->refresh()),
            'recovery' => $recovery,
            'saved_at' => now()->toIso8601String(),
        ];
    }
}
