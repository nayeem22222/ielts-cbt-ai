<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio\Pipeline;

use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class CleanupFailedAudioPipelineFilesAction
{
    /**
     * Clean up any partial processed/normalized/waveform files from a failed run.
     *
     * The original audio is NEVER deleted.
     *
     * @param  string|null  $versionTag  Only clean this specific version tag, or all if null.
     */
    public function execute(ListeningAudio $audio, ?string $versionTag = null): void
    {
        $keepOriginal = (bool) config('listening.audio_pipeline.storage.keep_original', true);

        if (! $keepOriginal) {
            return; // Configured to keep originals; nothing to do differently.
        }

        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        $dirsToClean = [
            'listening/audio/processed/'.$audio->id,
            'listening/audio/normalized/'.$audio->id,
            'listening/audio/waveforms/'.$audio->id,
            'listening/audio/previews/'.$audio->id,
        ];

        foreach ($dirsToClean as $baseDir) {
            $targetDir = $versionTag !== null
                ? $baseDir.'/'.$versionTag
                : $baseDir;

            try {
                if ($disk->exists($targetDir)) {
                    $files = $disk->allFiles($targetDir);

                    foreach ($files as $file) {
                        $disk->delete($file);
                    }
                }
            } catch (\Throwable) {
                // Non-critical cleanup; continue
            }
        }
    }
}
