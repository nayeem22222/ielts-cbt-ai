<?php

declare(strict_types=1);

namespace App\Jobs\Listening;

use App\Actions\Listening\Audio\GenerateListeningWaveformAction;
use App\Models\Listening\ListeningAudio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateListeningWaveformJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;

    public function __construct(
        public int $audioId,
    ) {
        $this->timeout = ((int) config('listening.audio.ffmpeg.timeout', 300)) + 60;
        $this->onQueue((string) config('listening.audio.queue', 'default'));
    }

    public function handle(GenerateListeningWaveformAction $generateWaveform): void
    {
        $audio = ListeningAudio::query()->find($this->audioId);

        if ($audio === null) {
            return;
        }

        $waveform = $generateWaveform->execute($audio);

        $audio->forceFill([
            'waveform_json_path' => $waveform->jsonPath,
            'preview_waveform_path' => $waveform->previewPath,
            'waveform_path' => $waveform->previewPath,
            'peaks' => $waveform->peaks,
            'meta' => array_merge($audio->meta ?? [], [
                'waveform_quality' => $waveform->quality,
                'waveform_generated_at' => $waveform->generatedAt,
            ]),
        ])->save();
    }
}
