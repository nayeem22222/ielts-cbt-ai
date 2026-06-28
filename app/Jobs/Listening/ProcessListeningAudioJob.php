<?php

declare(strict_types=1);

namespace App\Jobs\Listening;

use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\ListeningAudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessListeningAudioJob implements ShouldQueue
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

    public function handle(ListeningAudioProcessingService $processing): void
    {
        $audio = ListeningAudio::query()->find($this->audioId);

        if ($audio === null) {
            return;
        }

        $processing->process($audio);
    }
}
