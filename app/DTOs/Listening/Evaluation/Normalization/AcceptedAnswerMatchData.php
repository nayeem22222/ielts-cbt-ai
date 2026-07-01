<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation\Normalization;

final readonly class AcceptedAnswerMatchData
{
    /**
     * @param  list<string>  $normalizedCorrectAnswers
     * @param  list<array{step: string, before: mixed, after: mixed}>  $normalizationSteps
     */
    public function __construct(
        public bool $matched,
        public ?string $matchedValue,
        public ?string $matchedType,
        public string $matchReason,
        public NormalizedListeningAnswerData $normalizedStudentAnswer,
        public array $normalizedCorrectAnswers,
        public array $normalizationSteps,
    ) {}
}
