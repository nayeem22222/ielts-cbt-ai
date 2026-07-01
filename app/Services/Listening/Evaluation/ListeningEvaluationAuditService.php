<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptEvaluation;
use Illuminate\Support\Facades\Log;

class ListeningEvaluationAuditService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function logStarted(ListeningAttempt $attempt, ListeningAttemptEvaluation $evaluation, array $context = []): void
    {
        Log::info('listening.evaluation.started', array_merge([
            'attempt_id' => $attempt->id,
            'evaluation_id' => $evaluation->id,
            'user_id' => $attempt->user_id,
            'test_id' => $attempt->listening_test_id,
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logCompleted(ListeningAttemptEvaluation $evaluation, array $context = []): void
    {
        Log::info('listening.evaluation.completed', array_merge([
            'evaluation_id' => $evaluation->id,
            'attempt_id' => $evaluation->listening_attempt_id,
            'raw_score' => $evaluation->raw_score,
            'band_score' => $evaluation->band_score,
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logFailed(ListeningAttempt $attempt, \Throwable $exception, array $context = []): void
    {
        Log::error('listening.evaluation.failed', array_merge([
            'attempt_id' => $attempt->id,
            'message' => $exception->getMessage(),
        ], $context));
    }
}
