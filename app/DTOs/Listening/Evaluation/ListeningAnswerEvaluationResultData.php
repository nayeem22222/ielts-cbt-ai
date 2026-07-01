<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation;

use App\Enums\Listening\ListeningMatchStatus;

final readonly class ListeningAnswerEvaluationResultData
{
    /**
     * @param  list<array<string, mixed>>|null  $studentAnswerSnapshot
     * @param  list<array<string, mixed>>|null  $normalizedStudentAnswer
     * @param  list<array<string, mixed>>|null  $correctAnswerSnapshot
     * @param  list<array<string, mixed>>|null  $acceptedAnswersSnapshot
     * @param  list<array<string, mixed>>|null  $matchedAnswer
     * @param  list<string>  $normalizationSteps
     * @param  array<string, mixed>  $evaluatorMeta
     */
    public function __construct(
        public int $attemptAnswerId,
        public int $questionId,
        public int $questionNumber,
        public string $questionType,
        public ?array $studentAnswerSnapshot,
        public ?array $normalizedStudentAnswer,
        public ?array $correctAnswerSnapshot,
        public ?array $acceptedAnswersSnapshot,
        public ?array $matchedAnswer,
        public bool $isCorrect,
        public float $marksAvailable,
        public float $marksAwarded,
        public ListeningMatchStatus $matchStatus,
        public ?string $matchReason = null,
        public array $normalizationSteps = [],
        public array $evaluatorMeta = [],
    ) {}
}
