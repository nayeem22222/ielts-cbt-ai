<?php

declare(strict_types=1);

namespace App\Jobs\Listening\Audio;

use App\Actions\Listening\Audio\Pipeline\RetryStuckListeningAudioPipelineAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryStuckListeningAudioJobs implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public readonly int $limit = 50,
    ) {}

    public function handle(RetryStuckListeningAudioPipelineAction $action): void
    {
        $result = $action->execute(dryRun: false, limit: $this->limit);

        Log::channel('default')->info('RetryStuckListeningAudioJobs completed.', $result);
    }
}
