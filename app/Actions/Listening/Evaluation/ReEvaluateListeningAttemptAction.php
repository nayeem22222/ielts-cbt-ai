<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningEvaluationResultData;
use App\Enums\Listening\ListeningEvaluationType;
use App\Models\Listening\ListeningAttempt;
use App\Models\User;
use App\Services\Listening\Evaluation\ListeningAnswerEngineService;
use Illuminate\Auth\Access\AuthorizationException;
use App\Enums\Auth\UserRole;

class ReEvaluateListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAnswerEngineService $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(ListeningAttempt $attempt, User $admin, array $options = []): ListeningEvaluationResultData
    {
        if (! config('listening.answer_engine.allow_recheck', true)) {
            throw new AuthorizationException('Listening evaluation recheck is disabled.');
        }

        if (! $admin->hasRole(UserRole::Admin) && ! $admin->hasRole(UserRole::SuperAdmin)) {
            throw new AuthorizationException('Only administrators can recheck listening attempts.');
        }

        return $this->engine->evaluateAttempt($attempt, array_merge($options, [
            'evaluation_type' => ListeningEvaluationType::AdminRecheck,
            'evaluated_by' => $admin->id,
            'force' => true,
        ]));
    }
}
