<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\Actions\Listening\Result\BuildListeningResultAction;
use App\Actions\Listening\Result\HideListeningResultAction;
use App\Actions\Listening\Result\PublishListeningResultAction;
use App\Actions\Listening\Result\RebuildListeningResultAction;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningResultStatus;
use App\Jobs\Listening\Result\BuildListeningResultJob;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use App\Repositories\Listening\Evaluation\ListeningAttemptEvaluationRepository;
use App\Repositories\Listening\Result\ListeningResultRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListeningResultService
{
    public function __construct(
        private readonly ListeningResultRepository $results,
        private readonly ListeningAttemptEvaluationRepository $evaluations,
        private readonly BuildListeningResultAction $buildAction,
        private readonly PublishListeningResultAction $publishAction,
        private readonly HideListeningResultAction $hideAction,
        private readonly RebuildListeningResultAction $rebuildAction,
        private readonly ListeningResultVisibilityService $visibility,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getStudentResults(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->results->paginateForStudent($user, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAdminResults(array $filters = []): LengthAwarePaginator
    {
        return $this->results->paginateForAdmin($filters);
    }

    public function findForStudent(User $user, int $resultId): ?ListeningResult
    {
        $result = $this->results->findForStudent($user, $resultId);

        if ($result === null || ! $this->visibility->canStudentView($result, $user)) {
            return null;
        }

        return $result->load(['test.setting', 'attempt']);
    }

    public function findByAttempt(ListeningAttempt $attempt): ?ListeningResult
    {
        return $this->results->findLatestByAttempt($attempt)?->load(['test.setting', 'evaluation']);
    }

    public function buildFromEvaluation(ListeningAttemptEvaluation $evaluation, bool $force = false): ListeningResult
    {
        return $this->buildAction->execute($evaluation, $force);
    }

    public function publish(ListeningResult $result): ListeningResult
    {
        return $this->publishAction->execute($result);
    }

    public function hide(ListeningResult $result): ListeningResult
    {
        return $this->hideAction->execute($result);
    }

    public function rebuild(ListeningResult $result): ListeningResult
    {
        return $this->rebuildAction->execute($result);
    }

    public function ensureResultExistsForAttempt(ListeningAttempt $attempt): ?ListeningResult
    {
        $existing = $this->findByAttempt($attempt);

        if ($existing !== null) {
            return $existing;
        }

        $evaluation = $this->evaluations->getLatestForAttempt($attempt);

        if ($evaluation === null) {
            return $this->createPendingPlaceholder($attempt);
        }

        if (in_array($evaluation->status, [
            ListeningEvaluationStatus::Pending,
            ListeningEvaluationStatus::Processing,
        ], true)) {
            return $this->createPendingPlaceholder($attempt, $evaluation);
        }

        if ($evaluation->status === ListeningEvaluationStatus::Failed) {
            return $this->createFailedPlaceholder($attempt, $evaluation);
        }

        if (config('listening.results.auto_build_after_evaluation', true)) {
            BuildListeningResultJob::dispatch($evaluation->id);

            return $this->createPendingPlaceholder($attempt, $evaluation);
        }

        return $this->buildFromEvaluation($evaluation);
    }

    public function dispatchBuildForEvaluation(ListeningAttemptEvaluation $evaluation): void
    {
        if (! config('listening.results.auto_build_after_evaluation', true)) {
            return;
        }

        BuildListeningResultJob::dispatch($evaluation->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function studentViewData(ListeningResult $result): array
    {
        $settings = $result->test?->setting;
        $questionSummary = $this->visibility->filterQuestionSummaryForStudent(
            $result->question_summary ?? [],
            $settings,
        );

        return [
            'result' => $result,
            'test' => $result->test,
            'sectionBreakdown' => $result->section_breakdown ?? [],
            'questionTypeBreakdown' => $result->question_type_breakdown ?? [],
            'questionSummary' => $questionSummary,
            'showCorrectAnswers' => $this->visibility->showCorrectAnswers($settings),
        ];
    }

    private function createPendingPlaceholder(
        ListeningAttempt $attempt,
        ?ListeningAttemptEvaluation $evaluation = null,
    ): ListeningResult {
        $existing = $this->results->findLatestByAttempt($attempt);

        if ($existing !== null) {
            return $existing;
        }

        return $this->results->create([
            'listening_attempt_id' => $attempt->id,
            'listening_attempt_evaluation_id' => $evaluation?->id,
            'listening_test_id' => $attempt->listening_test_id,
            'user_id' => $attempt->user_id,
            'status' => ListeningResultStatus::Pending->value,
            'submitted_at' => $attempt->submitted_at ?? $attempt->auto_submitted_at,
            'is_visible_to_student' => $this->visibility->defaultVisibleToStudent(),
            'total_questions' => (int) ($attempt->total_questions ?: 40),
        ]);
    }

    private function createFailedPlaceholder(
        ListeningAttempt $attempt,
        ListeningAttemptEvaluation $evaluation,
    ): ListeningResult {
        $existing = $this->results->findLatestByAttempt($attempt);

        if ($existing !== null && $existing->status === ListeningResultStatus::Failed) {
            return $existing;
        }

        if ($existing !== null) {
            return $this->results->update($existing, [
                'status' => ListeningResultStatus::Failed->value,
                'listening_attempt_evaluation_id' => $evaluation->id,
                'meta' => [
                    'failure_reason' => $evaluation->errors['message'] ?? 'Evaluation failed.',
                ],
            ]);
        }

        return $this->results->create([
            'listening_attempt_id' => $attempt->id,
            'listening_attempt_evaluation_id' => $evaluation->id,
            'listening_test_id' => $attempt->listening_test_id,
            'user_id' => $attempt->user_id,
            'status' => ListeningResultStatus::Failed->value,
            'submitted_at' => $attempt->submitted_at ?? $attempt->auto_submitted_at,
            'evaluated_at' => $evaluation->finished_at,
            'is_visible_to_student' => $this->visibility->defaultVisibleToStudent(),
            'total_questions' => (int) ($evaluation->total_questions ?: $attempt->total_questions ?: 40),
            'meta' => [
                'failure_reason' => $evaluation->errors['message'] ?? 'Evaluation failed.',
            ],
        ]);
    }
}
