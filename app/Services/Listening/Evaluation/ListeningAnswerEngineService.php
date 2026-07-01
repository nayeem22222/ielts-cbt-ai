<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\DTOs\Listening\Evaluation\ListeningEvaluationResultData;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningEvaluationType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Evaluation\ListeningAttemptEvaluationRepository;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ListeningAnswerEngineService
{
    public function __construct(
        private readonly ListeningAttemptEvaluationService $evaluationService,
        private readonly ListeningAttemptEvaluationRepository $evaluations,
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningQuestionEvaluatorRegistry $evaluators,
        private readonly ListeningEvaluationAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function evaluateAttempt(ListeningAttempt $attempt, array $options = []): ListeningEvaluationResultData
    {
        if (! $this->canEvaluate($attempt)) {
            throw new \RuntimeException('Attempt is not eligible for evaluation.');
        }

        $version = (string) ($options['evaluation_version'] ?? config('listening.answer_engine.version', '1.0.0'));
        $evaluationTypeOption = $options['evaluation_type'] ?? ListeningEvaluationType::System;
        $type = $evaluationTypeOption instanceof ListeningEvaluationType
            ? $evaluationTypeOption
            : ListeningEvaluationType::from((string) $evaluationTypeOption);
        $evaluatedBy = isset($options['evaluated_by']) ? (int) $options['evaluated_by'] : null;
        $force = (bool) ($options['force'] ?? false);

        if (! $force && $type === ListeningEvaluationType::System) {
            $existing = $this->evaluations->findCompletedSystemEvaluation($attempt, $version);

            if ($existing !== null) {
                return $this->toResultData($existing);
            }
        }

        $lock = Cache::lock(
            'listening:evaluate:'.$attempt->id,
            (int) config('listening.answer_engine.lock_ttl_seconds', 120),
        );

        if (! $lock->get()) {
            throw new \RuntimeException('Evaluation already in progress for this attempt.');
        }

        try {
            if ($this->evaluations->hasActiveEvaluation($attempt) && ! $force) {
                throw new \RuntimeException('Evaluation already in progress for this attempt.');
            }

            return DB::transaction(function () use ($attempt, $version, $type, $evaluatedBy, $options): ListeningEvaluationResultData {
                $evaluation = $this->markAttemptEvaluationStarted($attempt, $version, $type, $evaluatedBy);
                $this->audit->logStarted($attempt, $evaluation, $options);

                try {
                    $answerResults = $this->evaluationService->evaluateAllAnswers($attempt, $evaluation);
                    $result = $this->evaluationService->finalizeEvaluation($attempt, $evaluation, $answerResults);

                    $this->markAttemptEvaluationCompleted($attempt, $evaluation, $result);
                    $this->audit->logCompleted($evaluation->refresh());

                    return $result;
                } catch (Throwable $exception) {
                    $this->markAttemptEvaluationFailed($attempt, $evaluation, $exception);
                    $this->audit->logFailed($attempt, $exception);

                    throw $exception;
                }
            });
        } finally {
            $lock->release();
        }
    }

    public function evaluateAnswer(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        return $this->evaluators->evaluate($attemptAnswer, $question);
    }

    public function evaluatorFor(ListeningQuestion $question): \App\Services\Listening\Evaluation\Evaluators\BaseListeningQuestionEvaluator
    {
        return $this->evaluators->evaluatorFor($question);
    }

    public function canEvaluate(ListeningAttempt $attempt): bool
    {
        return in_array($attempt->status, [
            ListeningAttemptStatus::Submitted,
            ListeningAttemptStatus::AutoSubmitted,
            ListeningAttemptStatus::Expired,
        ], true);
    }

    public function markAttemptEvaluationStarted(
        ListeningAttempt $attempt,
        string $version,
        ListeningEvaluationType $type = ListeningEvaluationType::System,
        ?int $evaluatedBy = null,
    ): ListeningAttemptEvaluation {
        $this->attempts->update($attempt, [
            'evaluation_status' => ListeningEvaluationStatus::Processing,
            'evaluation_version' => $version,
        ]);

        return $this->evaluationService->createEvaluationRecord($attempt, $version, $type, $evaluatedBy);
    }

    public function markAttemptEvaluationCompleted(
        ListeningAttempt $attempt,
        ListeningAttemptEvaluation $evaluation,
        ListeningEvaluationResultData $result,
    ): void {
        $status = $result->status;

        $this->evaluations->update($evaluation, [
            'status' => $status,
            'raw_score' => $result->rawScore,
            'total_correct' => $result->totalCorrect,
            'band_score' => $result->bandScore,
            'summary' => $result->summary,
            'finished_at' => now(),
        ]);

        $this->evaluationService->updateAttemptScore($attempt, $result);

        $this->attempts->update($attempt, [
            'evaluation_status' => $status,
            'evaluated_at' => now(),
            'evaluation_version' => $evaluation->evaluation_version,
            'evaluation_meta' => [
                'evaluation_id' => $evaluation->id,
                'evaluation_type' => $evaluation->evaluation_type?->value,
            ],
        ]);
    }

    public function markAttemptEvaluationFailed(
        ListeningAttempt $attempt,
        ListeningAttemptEvaluation $evaluation,
        Throwable $exception,
    ): void {
        $this->evaluations->update($evaluation, [
            'status' => ListeningEvaluationStatus::Failed,
            'errors' => ['message' => $exception->getMessage()],
            'finished_at' => now(),
        ]);

        $this->attempts->update($attempt, [
            'evaluation_status' => ListeningEvaluationStatus::Failed,
            'evaluation_meta' => [
                'evaluation_id' => $evaluation->id,
                'error' => $exception->getMessage(),
            ],
        ]);
    }

    private function toResultData(ListeningAttemptEvaluation $evaluation): ListeningEvaluationResultData
    {
        return new ListeningEvaluationResultData(
            evaluationId: $evaluation->id,
            status: $evaluation->status,
            rawScore: (float) $evaluation->raw_score,
            totalQuestions: (int) $evaluation->total_questions,
            totalCorrect: (float) $evaluation->total_correct,
            bandScore: $evaluation->band_score !== null ? (float) $evaluation->band_score : null,
            summary: $evaluation->summary,
        );
    }
}
