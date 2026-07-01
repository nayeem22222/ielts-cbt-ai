<?php

declare(strict_types=1);

namespace App\Actions\Listening\Result;

use App\DTOs\Listening\Result\ListeningResultData;
use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningResult;
use App\Repositories\Listening\Result\ListeningResultRepository;
use App\Services\Listening\Result\ListeningResultBuilderService;
use Illuminate\Support\Facades\DB;

class BuildListeningResultAction
{
    public function __construct(
        private readonly ListeningResultBuilderService $builder,
        private readonly ListeningResultRepository $results,
    ) {}

    public function execute(ListeningAttemptEvaluation $evaluation, bool $force = false): ListeningResult
    {
        return DB::transaction(function () use ($evaluation, $force): ListeningResult {
            $existing = $this->results->findLatestByAttemptId((int) $evaluation->listening_attempt_id);

            if ($existing !== null && ! $force) {
                $existingEvalId = (int) ($existing->listening_attempt_evaluation_id ?? 0);

                if ($existingEvalId > 0 && $existingEvalId >= (int) $evaluation->id) {
                    return $existing;
                }
            }

            $existingCode = $existing?->result_code;
            $built = $this->builder->build($evaluation, $existingCode);

            $attributes = $this->toAttributes($built);

            if ($existing !== null && ! $force) {
                if ($existing->status === ListeningResultStatus::Hidden) {
                    $attributes['status'] = ListeningResultStatus::Hidden->value;
                    $attributes['is_visible_to_student'] = $existing->is_visible_to_student;
                }

                return $this->results->update($existing, $attributes);
            }

            if ($existing !== null && $force) {
                return $this->results->update($existing, $attributes);
            }

            if ($built->status === ListeningResultStatus::Ready) {
                $attributes['published_at'] = now();
            }

            return $this->results->create($attributes);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(ListeningResultData $data): array
    {
        return [
            'listening_attempt_id' => $data->attemptId,
            'listening_attempt_evaluation_id' => $data->evaluationId,
            'listening_test_id' => $data->testId,
            'user_id' => $data->userId,
            'result_code' => $data->resultCode,
            'status' => $data->status->value,
            'raw_score' => $data->rawScore,
            'total_questions' => $data->totalQuestions,
            'total_correct' => $data->totalCorrect,
            'total_incorrect' => $data->totalIncorrect,
            'total_unanswered' => $data->totalUnanswered,
            'band_score' => $data->bandScore,
            'listening_duration_seconds' => $data->listeningDurationSeconds,
            'submitted_at' => $data->submittedAt,
            'evaluated_at' => $data->evaluatedAt,
            'is_visible_to_student' => $data->isVisibleToStudent,
            'section_breakdown' => $data->sectionBreakdown,
            'question_type_breakdown' => $data->questionTypeBreakdown,
            'question_summary' => $data->questionSummary,
            'result_snapshot' => $data->resultSnapshot,
            'meta' => $data->meta,
        ];
    }
}
