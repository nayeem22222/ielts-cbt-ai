<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAction;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningTimerService;

class SubmitListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly ListeningTimerService $timer,
        private readonly EvaluateListeningAttemptAction $evaluate,
    ) {}

    public function execute(ListeningAttempt $attempt, bool $auto = false): ListeningAttempt
    {
        if ($this->timer->isExpired($attempt) && ! $auto) {
            return app(AutoSubmitListeningAttemptAction::class)->execute($attempt);
        }

        $submitted = $this->attempts->markSubmitted($attempt, $auto);
        $this->evaluate->execute($submitted, ['dispatch_only' => true]);

        return $submitted->refresh();
    }
}
