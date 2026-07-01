<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation;

use App\Enums\Listening\ListeningEvaluationStatus;

final readonly class ListeningEvaluationResultData
{
    /**
     * @param  list<ListeningAnswerEvaluationResultData>  $answerResults
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $evaluationId,
        public ListeningEvaluationStatus $status,
        public float $rawScore,
        public int $totalQuestions,
        public float $totalCorrect,
        public ?float $bandScore,
        public array $answerResults = [],
        public array $errors = [],
        public ?array $summary = null,
    ) {}
}
