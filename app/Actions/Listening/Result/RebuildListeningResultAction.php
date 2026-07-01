<?php

declare(strict_types=1);

namespace App\Actions\Listening\Result;

use App\Models\Listening\ListeningResult;
use App\Repositories\Listening\Evaluation\ListeningAttemptEvaluationRepository;
use App\Actions\Listening\Result\BuildListeningResultAction;

class RebuildListeningResultAction
{
    public function __construct(
        private readonly ListeningAttemptEvaluationRepository $evaluations,
        private readonly BuildListeningResultAction $build,
    ) {}

    public function execute(ListeningResult $result): ListeningResult
    {
        $attempt = $result->attempt;

        if ($attempt === null) {
            return $result;
        }

        $evaluation = $this->evaluations->getLatestForAttempt($attempt);

        if ($evaluation === null) {
            return $result;
        }

        return $this->build->execute($evaluation, force: true);
    }
}
