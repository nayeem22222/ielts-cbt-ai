<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\ListeningAudioFlowStateData;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAudioFlowService;

class MarkListeningAudioStartedAction
{
    public function __construct(
        private readonly ListeningAudioFlowService $audioFlow,
    ) {}

    public function execute(ListeningAttempt $attempt, int $sectionNumber): ListeningAudioFlowStateData
    {
        return $this->audioFlow->markAudioStarted($attempt, $sectionNumber);
    }
}
