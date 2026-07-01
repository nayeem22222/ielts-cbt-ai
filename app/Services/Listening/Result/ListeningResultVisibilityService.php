<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\DTOs\Listening\Result\ListeningQuestionResultItemData;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;

class ListeningResultVisibilityService
{
    public function canStudentView(ListeningResult $result, User $user): bool
    {
        if ((int) $result->user_id !== (int) $user->id) {
            return false;
        }

        if (! $result->is_visible_to_student) {
            return false;
        }

        return ! in_array($result->status?->value, ['hidden'], true);
    }

    public function showCorrectAnswers(?ListeningTestSetting $settings): bool
    {
        if ($settings !== null) {
            return (bool) $settings->show_correct_answer;
        }

        return (bool) config('listening.results.show_correct_answers_default', true);
    }

    public function showAcceptedAnswersToStudents(): bool
    {
        return (bool) config('listening.results.show_accepted_answers_to_students', false);
    }

    /**
     * @param  list<array<string, mixed>>  $questionSummary
     * @return list<array<string, mixed>>
     */
    public function filterQuestionSummaryForStudent(
        array $questionSummary,
        ?ListeningTestSetting $settings,
    ): array {
        $showCorrect = $this->showCorrectAnswers($settings);
        $showAccepted = $this->showAcceptedAnswersToStudents();

        return array_map(function (array $item) use ($showCorrect, $showAccepted): array {
            unset($item['normalized_answer'], $item['normalization_steps'], $item['match_reason']);

            if (! $showCorrect) {
                unset($item['correct_answer']);
            }

            if (! $showAccepted) {
                unset($item['accepted_answers']);
            }

            return $item;
        }, $questionSummary);
    }

    /**
     * @param  list<ListeningQuestionResultItemData>  $items
     * @return list<array<string, mixed>>
     */
    public function mapQuestionSummaryForStudent(
        array $items,
        ?ListeningTestSetting $settings,
    ): array {
        $showCorrect = $this->showCorrectAnswers($settings);
        $showAccepted = $this->showAcceptedAnswersToStudents();

        return array_map(
            fn (ListeningQuestionResultItemData $item): array => $item->toStudentArray($showCorrect, $showAccepted),
            $items,
        );
    }

    /**
     * @param  list<ListeningQuestionResultItemData>  $items
     * @return list<array<string, mixed>>
     */
    public function mapQuestionSummaryForAdmin(array $items): array
    {
        return array_map(
            fn (ListeningQuestionResultItemData $item): array => $item->toAdminArray(),
            $items,
        );
    }

    public function defaultVisibleToStudent(): bool
    {
        return (bool) config('listening.results.visible_to_student_default', true);
    }
}
