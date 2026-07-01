<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Result;

use App\Enums\Listening\ListeningResultStatus;

final readonly class ListeningResultData
{
    /**
     * @param  list<array<string, mixed>>  $sectionBreakdown
     * @param  list<array<string, mixed>>  $questionTypeBreakdown
     * @param  list<array<string, mixed>>  $questionSummary
     * @param  array<string, mixed>  $resultSnapshot
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $attemptId,
        public int $evaluationId,
        public int $testId,
        public int $userId,
        public ?string $resultCode,
        public ListeningResultStatus $status,
        public float $rawScore,
        public int $totalQuestions,
        public float $totalCorrect,
        public float $totalIncorrect,
        public int $totalUnanswered,
        public ?float $bandScore,
        public ?int $listeningDurationSeconds,
        public ?string $submittedAt,
        public ?string $evaluatedAt,
        public bool $isVisibleToStudent,
        public array $sectionBreakdown,
        public array $questionTypeBreakdown,
        public array $questionSummary,
        public array $resultSnapshot,
        public array $meta = [],
    ) {}
}
