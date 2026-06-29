<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAutoSaveService;
use App\Services\Listening\Student\ListeningNavigationStateService;

class AutoSaveListeningBulkAnswersAction
{
    public function __construct(
        private readonly ListeningAutoSaveService $autoSave,
        private readonly ListeningNavigationStateService $navigationState,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function execute(
        ListeningAttempt $attempt,
        array $answers,
        ?int $currentSectionNumber = null,
        ?int $currentQuestionNumber = null,
    ): array {
        $response = $this->autoSave->bulkSave($attempt, $answers);

        if ($currentSectionNumber !== null && $currentQuestionNumber !== null) {
            $this->navigationState->updateCurrentPosition($attempt, $currentSectionNumber, $currentQuestionNumber);
            $response['navigation'] = $this->navigationState->getNavigationPayload($attempt);
        }

        return $response;
    }
}
