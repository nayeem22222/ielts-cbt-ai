<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioProcessingService;

class ConvertListeningAudioAction
{
    public function __construct(
        private readonly ListeningAudioProcessingService $processing,
    ) {}

    public function execute(ListeningAudio $audio, string $sourcePath, string $outputPath): string
    {
        return $this->processing->convert($audio, $sourcePath, $outputPath);
    }
}
