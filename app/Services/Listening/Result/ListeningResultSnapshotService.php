<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\DTOs\Listening\Result\ListeningQuestionResultItemData;
use App\DTOs\Listening\Result\ListeningResultData;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningTestSetting;

class ListeningResultSnapshotService
{
    /**
     * @param  list<array<string, mixed>>  $sectionBreakdown
     * @param  list<array<string, mixed>>  $questionTypeBreakdown
     * @param  list<array<string, mixed>>  $questionSummary
     * @return array<string, mixed>
     */
    public function build(
        ListeningAttemptEvaluation $evaluation,
        ListeningResultData $resultData,
        array $sectionBreakdown,
        array $questionTypeBreakdown,
        array $questionSummary,
        ?ListeningTestSetting $settings,
    ): array {
        $attempt = $evaluation->attempt;
        $test = $evaluation->test;

        return [
            'generated_at' => now()->toIso8601String(),
            'attempt' => [
                'id' => $attempt?->id,
                'status' => $attempt?->status?->value,
                'submitted_at' => $attempt?->submitted_at?->toIso8601String(),
                'auto_submitted_at' => $attempt?->auto_submitted_at?->toIso8601String(),
                'duration_seconds' => $attempt?->duration_seconds,
            ],
            'test' => [
                'id' => $test?->id,
                'title' => $test?->title,
                'slug' => $test?->slug,
                'test_code' => $test?->test_code,
            ],
            'evaluation' => [
                'id' => $evaluation->id,
                'version' => $evaluation->evaluation_version,
                'status' => $evaluation->status?->value,
                'type' => $evaluation->evaluation_type?->value,
                'finished_at' => $evaluation->finished_at?->toIso8601String(),
            ],
            'scoring' => [
                'raw_score' => $resultData->rawScore,
                'total_questions' => $resultData->totalQuestions,
                'total_correct' => $resultData->totalCorrect,
                'total_incorrect' => $resultData->totalIncorrect,
                'total_unanswered' => $resultData->totalUnanswered,
                'band_score' => $resultData->bandScore,
            ],
            'section_breakdown' => $sectionBreakdown,
            'question_type_breakdown' => $questionTypeBreakdown,
            'question_summary' => $questionSummary,
            'settings_snapshot' => $this->settingsSnapshot($settings),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsSnapshot(?ListeningTestSetting $settings): array
    {
        if ($settings === null) {
            return [
                'show_correct_answer' => (bool) config('listening.results.show_correct_answers_default', true),
                'show_accepted_answers_to_students' => (bool) config('listening.results.show_accepted_answers_to_students', false),
            ];
        }

        return [
            'show_correct_answer' => (bool) $settings->show_correct_answer,
            'allow_review_after_submit' => (bool) $settings->allow_review_after_submit,
            'show_accepted_answers_to_students' => (bool) config('listening.results.show_accepted_answers_to_students', false),
        ];
    }
}
