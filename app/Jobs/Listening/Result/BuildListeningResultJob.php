<?php

declare(strict_types=1);

namespace App\Jobs\Listening\Result;

use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningResult;
use App\Services\Listening\Result\ListeningResultService;
use App\Services\Listening\Review\ListeningReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildListeningResultJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $evaluationId,
        public readonly bool $force = false,
    ) {}

    public function handle(ListeningResultService $results): void
    {
        $evaluation = ListeningAttemptEvaluation::query()->find($this->evaluationId);

        if ($evaluation === null) {
            Log::warning('listening.result.job.missing_evaluation', ['evaluation_id' => $this->evaluationId]);

            return;
        }

        $results->buildFromEvaluation($evaluation, $this->force);

        $result = ListeningResult::query()
            ->where('listening_attempt_evaluation_id', $evaluation->id)
            ->latest('id')
            ->first();

        if ($result !== null) {
            app(ListeningReviewService::class)->dispatchBuildForResult($result);
        }
    }
}
