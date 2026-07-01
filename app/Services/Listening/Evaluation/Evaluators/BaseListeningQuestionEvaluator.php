<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\DTOs\Listening\Evaluation\ListeningNormalizedAnswerData;
use App\DTOs\Listening\Evaluation\Normalization\AcceptedAnswerMatchData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\ListeningAnswerNormalizationService;
use App\Services\Listening\Evaluation\ListeningEvaluationSnapshotService;
use App\Services\Listening\Evaluation\Normalization\ListeningAcceptedAnswerMatcher;
use App\Services\Listening\Evaluation\Normalization\ListeningWordLimitService;
use App\Support\Listening\Evaluation\ListeningMatchReason;

abstract class BaseListeningQuestionEvaluator
{
    public function __construct(
        protected readonly ListeningAnswerNormalizationService $normalizer,
        protected readonly ListeningEvaluationSnapshotService $snapshots,
        protected readonly ListeningAcceptedAnswerMatcher $matcher,
        protected readonly ListeningWordLimitService $wordLimit,
    ) {}

    abstract public function evaluate(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData;

    protected function marksAvailable(ListeningQuestion $question): float
    {
        return (float) ($question->marks ?? config('listening.questions.default_marks', 1));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function studentAnswer(ListeningAttemptAnswer $attemptAnswer): ?array
    {
        $answer = $attemptAnswer->student_answer;

        return is_array($answer) ? $answer : null;
    }

    protected function unanswered(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        return $this->buildResult(
            attemptAnswer: $attemptAnswer,
            question: $question,
            normalized: new ListeningNormalizedAnswerData([], ['empty_answer']),
            isCorrect: false,
            marksAwarded: 0.0,
            matchStatus: ListeningMatchStatus::Unanswered,
            matchReason: ListeningMatchReason::UNANSWERED,
        );
    }

    protected function manualReview(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
        string $reason,
        ?ListeningNormalizedAnswerData $normalized = null,
    ): ListeningAnswerEvaluationResultData {
        return $this->buildResult(
            attemptAnswer: $attemptAnswer,
            question: $question,
            normalized: $normalized ?? new ListeningNormalizedAnswerData([], []),
            isCorrect: false,
            marksAwarded: 0.0,
            matchStatus: ListeningMatchStatus::ManualReview,
            matchReason: $reason,
        );
    }

    protected function buildResult(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
        ListeningNormalizedAnswerData $normalized,
        bool $isCorrect,
        float $marksAwarded,
        ListeningMatchStatus $matchStatus,
        ?string $matchReason = null,
        ?array $matchedAnswer = null,
    ): ListeningAnswerEvaluationResultData {
        $correct = $this->snapshots->correctAnswerSnapshot($question);
        $accepted = $this->snapshots->acceptedAnswersSnapshot($question);

        return new ListeningAnswerEvaluationResultData(
            attemptAnswerId: $attemptAnswer->id,
            questionId: $question->id,
            questionNumber: (int) $question->question_number,
            questionType: $question->question_type->value,
            studentAnswerSnapshot: $this->snapshots->studentAnswerSnapshot($attemptAnswer),
            normalizedStudentAnswer: $this->normalizedPayload($normalized),
            correctAnswerSnapshot: $correct,
            acceptedAnswersSnapshot: $accepted,
            matchedAnswer: $matchedAnswer,
            isCorrect: $isCorrect,
            marksAvailable: $this->marksAvailable($question),
            marksAwarded: $marksAwarded,
            matchStatus: $matchStatus,
            matchReason: $matchReason,
            normalizationSteps: $normalized->steps,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizedPayload(ListeningNormalizedAnswerData $normalized): array
    {
        return array_map(
            fn (string $value): array => ['value' => $value, 'type' => $normalized->format ?? 'text'],
            $normalized->values,
        );
    }

    protected function normalizedFromMatch(AcceptedAnswerMatchData $match, ?string $format = null): ListeningNormalizedAnswerData
    {
        return new ListeningNormalizedAnswerData(
            values: $match->normalizedStudentAnswer->values,
            steps: $match->normalizationSteps,
            format: $format ?? ($match->normalizedStudentAnswer->items[0]['type'] ?? 'text'),
        );
    }

    protected function matchResult(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
        AcceptedAnswerMatchData $match,
        string $format = 'text',
    ): ListeningAnswerEvaluationResultData {
        return $this->buildResult(
            attemptAnswer: $attemptAnswer,
            question: $question,
            normalized: $this->normalizedFromMatch($match, $format),
            isCorrect: $match->matched,
            marksAwarded: $match->matched ? $this->marksAvailable($question) : 0.0,
            matchStatus: $match->matched ? ListeningMatchStatus::Correct : ListeningMatchStatus::Incorrect,
            matchReason: $match->matchReason,
            matchedAnswer: $match->matchedValue !== null
                ? [['value' => $match->matchedValue, 'type' => $match->matchedType ?? $format]]
                : null,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function correctAnswers(ListeningQuestion $question): array
    {
        $primary = $this->snapshots->correctAnswerSnapshot($question);
        $accepted = $this->snapshots->acceptedAnswersSnapshot($question);

        if ($primary === [] && $accepted === []) {
            return [];
        }

        return array_values(array_merge($primary, $accepted));
    }

    protected function hasCorrectAnswerKey(ListeningQuestion $question): bool
    {
        foreach ($this->correctAnswers($question) as $item) {
            if (trim((string) ($item['value'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
