<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioStorageService;

class DeleteListeningAudioFilesAction
{
    public function __construct(
        private readonly ListeningAudioStorageService $storage,
    ) {}

    public function execute(ListeningAudio $audio): void
    {
        $this->storage->deleteFiles($audio);
    }
}
