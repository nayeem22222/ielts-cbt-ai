<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningPhaseTransitionService;

class TransitionToTransferPhaseAction
{
    public function __construct(
        private readonly ListeningPhaseTransitionService $phases,
        private readonly ValidateListeningPhaseTransitionAction $validate,
    ) {}

    public function execute(ListeningAttempt $attempt): ListeningAttempt
    {
        $this->validate->execute($attempt, \App\Enums\Listening\ListeningAttemptPhase::Transfer);

        return $this->phases->transitionToTransfer($attempt);
    }
}
