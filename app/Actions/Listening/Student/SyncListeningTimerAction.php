<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\ListeningTimerStateData;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningOfficialTimerService;

class SyncListeningTimerAction
{
    public function __construct(
        private readonly ListeningOfficialTimerService $timer,
    ) {}

    /**
     * @param  array<string, mixed>  $clientState
     */
    public function execute(ListeningAttempt $attempt, array $clientState = []): ListeningTimerStateData
    {
        return $this->timer->sync($attempt, $clientState);
    }
}
