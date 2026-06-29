<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\ListeningTimerStateData;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningOfficialTimerService;

class StartListeningTimerAction
{
    public function __construct(
        private readonly ListeningOfficialTimerService $timer,
    ) {}

    public function execute(ListeningAttempt $attempt): ListeningTimerStateData
    {
        return $this->timer->startTimer($attempt);
    }
}
