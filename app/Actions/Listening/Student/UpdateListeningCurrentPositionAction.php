<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\NavigationStateData;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningNavigationStateService;

class UpdateListeningCurrentPositionAction
{
    public function __construct(
        private readonly ListeningNavigationStateService $navigationState,
    ) {}

    public function execute(
        ListeningAttempt $attempt,
        int $sectionNumber,
        int $questionNumber,
        ?string $direction = null,
    ): NavigationStateData {
        if ($direction === 'next') {
            $next = $this->navigationState->getNextQuestion($attempt);

            if ($next !== null) {
                $questionNumber = $next;
                $sectionNumber = $this->navigationState->getSectionByQuestionNumber($attempt, $questionNumber);
            }
        } elseif ($direction === 'previous') {
            $previous = $this->navigationState->getPreviousQuestion($attempt);

            if ($previous !== null) {
                $questionNumber = $previous;
                $sectionNumber = $this->navigationState->getSectionByQuestionNumber($attempt, $questionNumber);
            }
        } elseif ($direction === 'section_switch') {
            $sectionNumber = max(1, min(4, $sectionNumber));
            $questionNumber = match ($sectionNumber) {
                1 => 1,
                2 => 11,
                3 => 21,
                default => 31,
            };
        }

        return $this->navigationState->updateCurrentPosition($attempt, $sectionNumber, $questionNumber);
    }
}
