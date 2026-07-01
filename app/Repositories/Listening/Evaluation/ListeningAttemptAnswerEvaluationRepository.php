<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Evaluation;

use App\Models\Listening\ListeningAttemptAnswerEvaluation;
use App\Models\Listening\ListeningAttemptEvaluation;

class ListeningAttemptAnswerEvaluationRepository
{
    public function create(array $attributes): ListeningAttemptAnswerEvaluation
    {
        return ListeningAttemptAnswerEvaluation::query()->create($attributes);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function createMany(ListeningAttemptEvaluation $evaluation, array $rows): void
    {
        foreach ($rows as $row) {
            $this->create(array_merge($row, [
                'listening_attempt_evaluation_id' => $evaluation->id,
                'listening_attempt_id' => $evaluation->listening_attempt_id,
            ]));
        }
    }
}
