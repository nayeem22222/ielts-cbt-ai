<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioProcessingService;

class RetryListeningAudioProcessingAction
{
    public function __construct(
        private readonly \App\Services\Listening\Audio\ListeningAudioService $audios,
    ) {}

    public function execute(ListeningAudio $audio, bool $force = false): void
    {
        $this->audios->retryProcessing($audio, $force);
    }
}
