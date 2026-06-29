<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningPhaseTransitionService;
use Illuminate\Validation\ValidationException;

class ValidateListeningPhaseTransitionAction
{
    public function __construct(
        private readonly ListeningPhaseTransitionService $phases,
    ) {}

    public function execute(ListeningAttempt $attempt, ListeningAttemptPhase $targetPhase): void
    {
        $errors = $this->phases->validateTransition($attempt, $targetPhase);

        if ($errors !== []) {
            throw ValidationException::withMessages(['phase' => $errors]);
        }
    }
}
