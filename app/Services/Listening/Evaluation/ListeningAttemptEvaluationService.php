<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\Actions\Listening\Evaluation\CalculateListeningBandScoreAction;
use App\Actions\Listening\Evaluation\CreateListeningEvaluationSnapshotAction;
use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAnswerAction;
use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\DTOs\Listening\Evaluation\ListeningEvaluationResultData;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningEvaluationType;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Repositories\Listening\Evaluation\ListeningAttemptAnswerEvaluationRepository;
use App\Repositories\Listening\Evaluation\ListeningAttemptEvaluationRepository;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Collection;

class ListeningAttemptEvaluationService
{
    public function __construct(
        private readonly ListeningAttemptEvaluationRepository $evaluations,
        private readonly ListeningAttemptAnswerEvaluationRepository $answerEvaluations,
        private readonly ListeningAttemptRepository $attempts,
        private readonly EvaluateListeningAttemptAnswerAction $evaluateAnswerAction,
        private readonly CreateListeningEvaluationSnapshotAction $snapshotAction,
        private readonly CalculateListeningBandScoreAction $bandScoreAction,
    ) {}

    public function createEvaluationRecord(
        ListeningAttempt $attempt,
        string $version,
        ListeningEvaluationType $type = ListeningEvaluationType::System,
        ?int $evaluatedBy = null,
    ): ListeningAttemptEvaluation {
        return $this->evaluations->create([
            'listening_attempt_id' => $attempt->id,
            'listening_test_id' => $attempt->listening_test_id,
            'user_id' => $attempt->user_id,
            'evaluation_version' => $version,
            'status' => ListeningEvaluationStatus::Processing,
            'total_questions' => (int) ($attempt->total_questions ?: 40),
            'evaluation_type' => $type,
            'evaluated_by' => $evaluatedBy,
            'started_at' => now(),
        ]);
    }

    /**
     * @return list<ListeningAnswerEvaluationResultData>
     */
    public function evaluateAllAnswers(
        ListeningAttempt $attempt,
        ListeningAttemptEvaluation $evaluation,
    ): array {
        $results = [];
        $rows = [];

        /** @var Collection<int, \App\Models\Listening\ListeningAttemptAnswer> $answers */
        $answers = $this->attempts->answersForAttempt($attempt)->load('question');

        foreach ($answers as $attemptAnswer) {
            $question = $attemptAnswer->question;

            if ($question === null || ! $question->is_active) {
                continue;
            }

            $result = $this->evaluateAnswerAction->execute($attemptAnswer, $question);
            $results[] = $result;
            $rows[] = $this->snapshotAction->execute($result);
        }

        $this->answerEvaluations->createMany($evaluation, $rows);

        return $results;
    }

    /**
     * @param  list<ListeningAnswerEvaluationResultData>  $answerResults
     */
    public function finalizeEvaluation(
        ListeningAttempt $attempt,
        ListeningAttemptEvaluation $evaluation,
        array $answerResults,
    ): ListeningEvaluationResultData {
        $totalCorrect = round(array_sum(array_map(
            fn (ListeningAnswerEvaluationResultData $r): float => $r->marksAwarded,
            $answerResults,
        )), 2);

        $rawScore = $totalCorrect;
        $bandData = $this->bandScoreAction->execute($rawScore, (int) $evaluation->total_questions);

        $needsReview = collect($answerResults)->contains(
            fn (ListeningAnswerEvaluationResultData $r): bool => $r->matchStatus === ListeningMatchStatus::ManualReview,
        );

        $status = $needsReview
            ? ListeningEvaluationStatus::NeedsReview
            : ListeningEvaluationStatus::Completed;

        $summary = [
            'correct' => collect($answerResults)->where('isCorrect', true)->count(),
            'incorrect' => collect($answerResults)->where('matchStatus', ListeningMatchStatus::Incorrect)->count(),
            'unanswered' => collect($answerResults)->where('matchStatus', ListeningMatchStatus::Unanswered)->count(),
            'partial' => collect($answerResults)->where('matchStatus', ListeningMatchStatus::Partial)->count(),
            'manual_review' => collect($answerResults)->where('matchStatus', ListeningMatchStatus::ManualReview)->count(),
        ];

        return new ListeningEvaluationResultData(
            evaluationId: $evaluation->id,
            status: $status,
            rawScore: $rawScore,
            totalQuestions: (int) $evaluation->total_questions,
            totalCorrect: $totalCorrect,
            bandScore: $bandData->bandScore,
            answerResults: $answerResults,
            summary: $summary,
        );
    }

    public function updateAttemptScore(ListeningAttempt $attempt, ListeningEvaluationResultData $result): void
    {
        $this->attempts->update($attempt, [
            'total_correct' => (int) round($result->totalCorrect),
            'raw_score' => (int) round($result->rawScore),
            'band_score' => $result->bandScore,
            'result_meta' => array_merge(is_array($attempt->result_meta) ? $attempt->result_meta : [], [
                'evaluation_id' => $result->evaluationId,
                'summary' => $result->summary,
            ]),
        ]);
    }

    public function getLatestEvaluation(ListeningAttempt $attempt): ?ListeningAttemptEvaluation
    {
        return $this->evaluations->getLatestForAttempt($attempt);
    }

    public function needsEvaluation(ListeningAttempt $attempt): bool
    {
        if (! in_array($attempt->status->value, ['submitted', 'auto_submitted', 'expired'], true)) {
            return false;
        }

        if ($attempt->evaluation_status === null) {
            return true;
        }

        return in_array($attempt->evaluation_status, [
            ListeningEvaluationStatus::Pending,
            ListeningEvaluationStatus::Failed,
        ], true);
    }
}
