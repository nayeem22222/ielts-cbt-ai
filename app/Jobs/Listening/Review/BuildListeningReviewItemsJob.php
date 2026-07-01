<?php

declare(strict_types=1);

namespace App\Jobs\Listening\Review;

use App\Models\Listening\ListeningResult;
use App\Services\Listening\Review\ListeningReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildListeningReviewItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $resultId,
    ) {}

    public function handle(ListeningReviewService $reviews): void
    {
        $result = ListeningResult::query()->find($this->resultId);

        if ($result === null) {
            Log::warning('listening.review.job.missing_result', ['result_id' => $this->resultId]);

            return;
        }

        $reviews->rebuildReviewItems($result);
    }
}
