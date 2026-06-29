<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAutoSaveService;
use App\Services\Listening\Student\ListeningPlayerRecoveryService;

class RecoverListeningDraftAnswersAction
{
    public function __construct(
        private readonly ListeningPlayerRecoveryService $recovery,
        private readonly ListeningAutoSaveService $autoSave,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function execute(ListeningAttempt $attempt, array $answers): array
    {
        $applied = $this->recovery->applyRecovery($attempt, $answers);

        return array_merge($this->autoSave->getSaveResponse($attempt->refresh()), [
            'success' => true,
            'applied_count' => count($applied),
            'results' => $applied,
        ]);
    }
}
