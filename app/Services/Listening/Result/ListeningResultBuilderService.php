<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\Actions\Listening\Result\GenerateListeningResultCodeAction;
use App\DTOs\Listening\Result\ListeningResultData;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTestSetting;
use Illuminate\Support\Collection;

class ListeningResultBuilderService
{
    public function __construct(
        private readonly ListeningResultBreakdownService $breakdown,
        private readonly ListeningResultSnapshotService $snapshot,
        private readonly ListeningResultVisibilityService $visibility,
        private readonly GenerateListeningResultCodeAction $generateCode,
    ) {}

    public function build(ListeningAttemptEvaluation $evaluation, ?string $existingCode = null): ListeningResultData
    {
        $evaluation->loadMissing([
            'attempt',
            'test.setting',
            'answerEvaluations',
        ]);

        $answerEvaluations = $evaluation->answerEvaluations;
        $sections = $this->loadSections((int) $evaluation->listening_test_id);

        $sectionBreakdown = $this->buildSectionBreakdown($answerEvaluations, $sections);
        $questionTypeBreakdown = $this->buildQuestionTypeBreakdown($answerEvaluations);
        $summaryItems = $this->buildQuestionSummary($answerEvaluations, $sections);
        $totals = $this->calculateTotals($answerEvaluations);

        $settings = $evaluation->test?->setting;
        $status = $this->resolveStatus($evaluation);
        $questionSummaryAdmin = $this->visibility->mapQuestionSummaryForAdmin($summaryItems);

        $resultData = new ListeningResultData(
            attemptId: (int) $evaluation->listening_attempt_id,
            evaluationId: (int) $evaluation->id,
            testId: (int) $evaluation->listening_test_id,
            userId: (int) $evaluation->user_id,
            resultCode: $existingCode ?? $this->generateCode->execute(),
            status: $status,
            rawScore: (float) ($evaluation->raw_score ?? $totals['raw_score']),
            totalQuestions: (int) ($evaluation->total_questions ?: $answerEvaluations->count() ?: 40),
            totalCorrect: (float) ($evaluation->total_correct ?? $totals['total_correct']),
            totalIncorrect: $totals['total_incorrect'],
            totalUnanswered: $totals['total_unanswered'],
            bandScore: $evaluation->band_score !== null ? (float) $evaluation->band_score : null,
            listeningDurationSeconds: $evaluation->attempt?->duration_seconds,
            submittedAt: $evaluation->attempt?->submitted_at?->toDateTimeString()
                ?? $evaluation->attempt?->auto_submitted_at?->toDateTimeString(),
            evaluatedAt: $evaluation->finished_at?->toDateTimeString(),
            isVisibleToStudent: $this->visibility->defaultVisibleToStudent(),
            sectionBreakdown: $sectionBreakdown,
            questionTypeBreakdown: $questionTypeBreakdown,
            questionSummary: $questionSummaryAdmin,
            resultSnapshot: [],
            meta: [
                'evaluation_version' => $evaluation->evaluation_version,
                'evaluation_status' => $evaluation->status?->value,
            ],
        );

        $snapshot = $this->buildResultSnapshot(
            $evaluation,
            $resultData,
            $sectionBreakdown,
            $questionTypeBreakdown,
            $questionSummaryAdmin,
            $settings,
        );

        return new ListeningResultData(
            attemptId: $resultData->attemptId,
            evaluationId: $resultData->evaluationId,
            testId: $resultData->testId,
            userId: $resultData->userId,
            resultCode: $resultData->resultCode,
            status: $resultData->status,
            rawScore: $resultData->rawScore,
            totalQuestions: $resultData->totalQuestions,
            totalCorrect: $resultData->totalCorrect,
            totalIncorrect: $resultData->totalIncorrect,
            totalUnanswered: $resultData->totalUnanswered,
            bandScore: $resultData->bandScore,
            listeningDurationSeconds: $resultData->listeningDurationSeconds,
            submittedAt: $resultData->submittedAt,
            evaluatedAt: $resultData->evaluatedAt,
            isVisibleToStudent: $resultData->isVisibleToStudent,
            sectionBreakdown: $resultData->sectionBreakdown,
            questionTypeBreakdown: $resultData->questionTypeBreakdown,
            questionSummary: $resultData->questionSummary,
            resultSnapshot: $snapshot,
            meta: $resultData->meta,
        );
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @param  Collection<int, ListeningSection>  $sections
     * @return list<array<string, mixed>>
     */
    public function buildSectionBreakdown(Collection $answerEvaluations, Collection $sections): array
    {
        return $this->breakdown->buildSectionBreakdown($answerEvaluations, $sections);
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @return list<array<string, mixed>>
     */
    public function buildQuestionTypeBreakdown(Collection $answerEvaluations): array
    {
        return $this->breakdown->buildQuestionTypeBreakdown($answerEvaluations);
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @param  Collection<int, ListeningSection>  $sections
     * @return list<\App\DTOs\Listening\Result\ListeningQuestionResultItemData>
     */
    public function buildQuestionSummary(Collection $answerEvaluations, Collection $sections): array
    {
        return $this->breakdown->buildQuestionSummaryItems($answerEvaluations, $sections);
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @return array{total_correct: float, total_incorrect: float, total_unanswered: int, raw_score: float}
     */
    public function calculateTotals(Collection $answerEvaluations): array
    {
        return $this->breakdown->calculateTotals($answerEvaluations);
    }

    /**
     * @param  list<array<string, mixed>>  $sectionBreakdown
     * @param  list<array<string, mixed>>  $questionTypeBreakdown
     * @param  list<array<string, mixed>>  $questionSummary
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(
        ListeningAttemptEvaluation $evaluation,
        ListeningResultData $resultData,
        array $sectionBreakdown,
        array $questionTypeBreakdown,
        array $questionSummary,
        ?ListeningTestSetting $settings,
    ): array {
        return $this->snapshot->build(
            $evaluation,
            $resultData,
            $sectionBreakdown,
            $questionTypeBreakdown,
            $questionSummary,
            $settings,
        );
    }

    private function resolveStatus(ListeningAttemptEvaluation $evaluation): ListeningResultStatus
    {
        return match ($evaluation->status) {
            ListeningEvaluationStatus::Failed => ListeningResultStatus::Failed,
            ListeningEvaluationStatus::Pending,
            ListeningEvaluationStatus::Processing => ListeningResultStatus::Pending,
            ListeningEvaluationStatus::Completed,
            ListeningEvaluationStatus::NeedsReview => ListeningResultStatus::Ready,
            default => ListeningResultStatus::Pending,
        };
    }

    /**
     * @return Collection<int, ListeningSection>
     */
    private function loadSections(int $testId): Collection
    {
        return ListeningSection::query()
            ->where('listening_test_id', $testId)
            ->orderBy('section_number')
            ->get();
    }
}
