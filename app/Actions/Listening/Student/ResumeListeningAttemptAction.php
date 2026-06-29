<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Repositories\Listening\Student\ListeningAttemptRepository;

class ResumeListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
    ) {}

    public function execute(User $user, ListeningTest $test): ?ListeningAttempt
    {
        return $this->attempts->findInProgressForUserAndTest($user->id, $test->id);
    }
}
