<?php

declare(strict_types=1);

namespace App\Services\Exam\Scoring;

readonly class QuestionScoreOutcome
{
    public function __construct(
        public bool $isCorrect,
        public float $scoreAwarded,
        public float $maxScore,
        public float $partialRatio,
        public ?string $studentResponse = null,
        public ?string $expectedResponse = null,
        public ?string $feedback = null,
    ) {
    }
}
