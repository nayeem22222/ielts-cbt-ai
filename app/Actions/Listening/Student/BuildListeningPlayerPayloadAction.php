<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\ListeningPlayerPayloadData;
use App\DTOs\Listening\Student\ListeningTimerPayloadData;
use App\Models\Listening\ListeningAttempt;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use App\Services\Listening\Student\ListeningAudioFlowService;
use App\Services\Listening\Student\ListeningNavigationService;
use App\Services\Listening\Student\ListeningOfficialFlowService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use App\Services\Listening\Student\ListeningPlayerDataService;
use App\Services\Listening\Student\ListeningPlayerRecoveryService;
use App\Services\Listening\Student\ListeningQuestionPaletteService;
use App\Services\Listening\Student\ListeningTimerService;

class BuildListeningPlayerPayloadAction
{
    public function __construct(
        private readonly ListeningPlayerDataService $playerData,
        private readonly ListeningNavigationService $navigation,
        private readonly ListeningTimerService $timer,
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningQuestionPaletteService $palette,
        private readonly ListeningPlayerRecoveryService $recovery,
        private readonly ListeningOfficialTimerService $officialTimer,
        private readonly ListeningOfficialFlowService $officialFlow,
        private readonly ListeningAudioFlowService $audioFlow,
    ) {}

    public function execute(ListeningAttempt $attempt): ListeningPlayerPayloadData
    {
        $attempt->loadMissing([
            'test.sections.audio',
            'test.questionGroups.section',
            'test.questions.section',
            'answers',
        ]);

        $test = $attempt->test;
        $answerRows = $this->answers->keyedByQuestionId($attempt);
        $currentQuestion = (int) $attempt->current_question_number ?: 1;
        $currentSection = (int) $attempt->current_section_number ?: $this->navigation->sectionForQuestionNumber($currentQuestion);

        $groups = [];
        $questions = [];
        $questionsByGroupId = [];

        foreach ($test?->questions()->where('is_active', true)->orderBy('question_number')->get() ?? [] as $question) {
            $attemptWithAnswers = clone $attempt;
            $attemptWithAnswers->setRelation('answers', $answerRows->values());
            $sanitizedQuestion = $this->playerData->sanitizeQuestion($question, $attemptWithAnswers);
            $questions[] = $sanitizedQuestion;
            $questionsByGroupId[(int) $sanitizedQuestion['group_id']][] = $sanitizedQuestion;
        }

        foreach ($test?->questionGroups()->where('is_active', true)->orderBy('start_question_number')->get() ?? [] as $group) {
            $groupQuestions = $questionsByGroupId[(int) $group->id] ?? [];
            $groups[] = $this->playerData->sanitizeGroup($group, $attempt, $groupQuestions);
        }

        $remaining = $this->officialTimer->getTotalRemainingSeconds($attempt);
        $officialTimer = $this->officialTimer->getState($attempt);
        $phaseState = $this->officialFlow->getPhaseState($attempt);

        return new ListeningPlayerPayloadData(
            attemptId: $attempt->id,
            testTitle: (string) ($test?->title ?? 'Listening Test'),
            testTypeLabel: strtoupper((string) ($test?->test_type?->label() ?? 'Academic')),
            currentSectionNumber: $currentSection,
            currentQuestionNumber: $currentQuestion,
            sections: $this->playerData->buildSectionsPayload($attempt),
            groups: $groups,
            questions: $questions,
            palette: $this->palette->build($attempt),
            audioSections: $this->playerData->buildSectionsPayload($attempt),
            timer: new ListeningTimerPayloadData(
                remainingSeconds: $remaining,
                totalSeconds: $this->timer->totalSeconds($attempt),
                serverNow: now()->toIso8601String(),
                expiresAt: $attempt->expires_at?->toIso8601String() ?? '',
                isExpired: $this->officialTimer->isExpired($attempt),
            ),
            routes: $this->playerData->playerRoutes($attempt),
            config: $this->playerData->playerConfig(),
            recovery: $this->recovery->buildRecoveryPayload($attempt),
            officialTimer: $officialTimer->toArray(),
            phase: $phaseState->toArray(),
            audioFlow: $this->audioFlow->getAudioState($attempt),
        );
    }
}
