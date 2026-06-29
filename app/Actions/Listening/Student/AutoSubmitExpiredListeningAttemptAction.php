<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAutoSubmitService;

class AutoSubmitExpiredListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAutoSubmitService $autoSubmit,
    ) {}

    public function execute(ListeningAttempt $attempt, string $reason = 'timer_expired'): ListeningAttempt
    {
        return $this->autoSubmit->autoSubmit($attempt, $reason);
    }
}
