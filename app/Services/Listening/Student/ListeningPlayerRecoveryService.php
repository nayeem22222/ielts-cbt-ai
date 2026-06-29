<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\PlayerRecoveryData;
use App\Models\Listening\ListeningAttempt;

class ListeningPlayerRecoveryService
{
    public function __construct(
        private readonly ListeningAnswerDraftService $drafts,
        private readonly ListeningAutoSaveService $autoSave,
        private readonly ListeningQuestionPaletteService $palette,
    ) {}

    /**
     * @param  array<string, mixed>  $clientDraft
     */
    public function detectUnsavedClientAnswers(array $clientDraft, array $serverSnapshot): PlayerRecoveryData
    {
        $comparison = $this->drafts->compareClientDraftWithServer($clientDraft, $serverSnapshot);
        $recoverable = $comparison['recoverable'] ?? [];

        $unsaved = array_values(array_map(function (array $item): array {
            return [
                'question_id' => (int) ($item['question_id'] ?? 0),
                'question_number' => (int) ($item['question_number'] ?? 0),
                'answer' => $item['answer'] ?? null,
                'hash' => (string) ($item['hash'] ?? ''),
                'updated_at' => (string) ($item['updated_at'] ?? ''),
            ];
        }, $recoverable));

        return new PlayerRecoveryData(
            hasUnsaved: count($unsaved) > 0,
            unsavedCount: count($unsaved),
            unsavedAnswers: $unsaved,
            serverSnapshot: $this->sanitizeSnapshot($serverSnapshot),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRecoveryPayload(ListeningAttempt $attempt): array
    {
        $snapshot = $this->drafts->getServerAnswerSnapshot($attempt);

        return [
            'draft_key' => $this->drafts->buildDraftKey($attempt),
            'server_snapshot' => $this->sanitizeSnapshot($snapshot),
            'enabled' => (bool) config('listening.recovery.enabled', true),
            'show_modal' => (bool) config('listening.recovery.show_recovery_modal', true),
            'counts' => [
                'answered' => $this->palette->countAnswered($attempt),
                'unanswered' => $this->palette->countUnanswered($attempt),
                'flagged' => $this->palette->countFlagged($attempt),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return list<array<string, mixed>>
     */
    public function applyRecovery(ListeningAttempt $attempt, array $answers): array
    {
        return $this->drafts->recoverDraftAnswers($attempt, $answers);
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeSnapshot(array $snapshot): array
    {
        $sanitized = [];

        foreach ($snapshot as $number => $item) {
            $sanitized[(int) $number] = [
                'question_id' => (int) ($item['question_id'] ?? 0),
                'question_number' => (int) ($item['question_number'] ?? $number),
                'answer' => $item['answer'] ?? null,
                'hash' => (string) ($item['hash'] ?? ''),
                'updated_at' => (string) ($item['updated_at'] ?? ''),
                'is_flagged' => (bool) ($item['is_flagged'] ?? false),
            ];
        }

        return $sanitized;
    }
}
