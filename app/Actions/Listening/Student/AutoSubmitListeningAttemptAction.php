<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAutoSubmitService;

class AutoSubmitListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAutoSubmitService $autoSubmit,
    ) {}

    public function execute(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->autoSubmit->autoSubmit($attempt);
    }
}
