<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningWaveformData;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningWaveformService;

class GenerateListeningWaveformAction
{
    public function __construct(
        private readonly ListeningWaveformService $waveforms,
    ) {}

    public function execute(ListeningAudio $audio): ListeningWaveformData
    {
        return $this->waveforms->generate($audio);
    }
}
