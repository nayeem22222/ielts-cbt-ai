<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Listening\Student\ListeningAttemptService;

class StartListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
    ) {}

    public function execute(User $user, ListeningTest $test, array $clientMeta = []): ListeningAttempt
    {
        return $this->attempts->start($user, $test, $clientMeta);
    }
}
