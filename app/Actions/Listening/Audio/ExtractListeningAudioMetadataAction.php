<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningAudioMetadataData;
use App\Services\Listening\Audio\ListeningAudioMetadataService;
use App\Services\Listening\Audio\ListeningAudioStorageService;

class ExtractListeningAudioMetadataAction
{
    public function __construct(
        private readonly ListeningAudioMetadataService $metadata,
        private readonly ListeningAudioStorageService $storage,
    ) {}

    public function execute(string $absolutePath): ListeningAudioMetadataData
    {
        return $this->metadata->extract($absolutePath);
    }
}
