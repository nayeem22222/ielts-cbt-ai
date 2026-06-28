<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio;

use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioService;
use Illuminate\Http\UploadedFile;

class UploadListeningAudioAction
{
    public function __construct(
        private readonly ListeningAudioService $audios,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(UploadedFile $file, array $data, ?int $uploadedBy = null): ListeningAudio
    {
        return $this->audios->createFromUpload($file, $data, $uploadedBy);
    }
}
