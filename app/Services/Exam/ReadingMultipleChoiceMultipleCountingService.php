<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Models\ReadingAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use Illuminate\Support\Collection;

class ReadingMultipleChoiceMultipleCountingService
{
    public function isMcqMultipleGroup(?ReadingQuestionGroup $group): bool
    {
        if ($group === null) {
            return false;
        }

        $type = $group->question_type;

        if ($type instanceof OfficialReadingQuestionType) {
            return $type === OfficialReadingQuestionType::MultipleChoiceMultiple;
        }

        return (string) $type === OfficialReadingQuestionType::MultipleChoiceMultiple->value;
    }

    public function groupQuestionCount(ReadingQuestionGroup $group): int
    {
        if ($group->start_question !== null && $group->end_question !== null) {
            return max(1, (int) $group->end_question - (int) $group->start_question + 1);
        }

        return max(1, $group->questions->filter(
            fn (ReadingQuestion $question): bool => (int) $question->question_number > 0,
        )->count());
    }

    /**
     * @return list<int>
     */
    public function groupQuestionNumbers(ReadingQuestionGroup $group): array
    {
        if ($group->start_question !== null && $group->end_question !== null) {
            $numbers = [];

            for ($number = (int) $group->start_question; $number <= (int) $group->end_question; $number++) {
                $numbers[] = $number;
            }

            return $numbers;
        }

        return $group->questions
            ->filter(fn (ReadingQuestion $question): bool => (int) $question->question_number > 0)
            ->sortBy('question_number')
            ->pluck('question_number')
            ->map(fn ($number): int => (int) $number)
            ->values()
            ->all();
    }

    public function countSelected(?array $answerJson): int
    {
        if (! is_array($answerJson) || $answerJson === []) {
            return 0;
        }

        return count(array_values(array_filter(
            array_map(static fn ($value): string => trim((string) $value), $answerJson),
            static fn (string $value): bool => $value !== '',
        )));
    }

    public function groupIsComplete(int $selectedCount, int $questionCount): bool
    {
        return $selectedCount > 0 && $selectedCount === $questionCount;
    }

    /**
     * @return array{start: int, end: int, required: int, selected: int, complete: bool}
     */
    public function resolveGroupSelectionState(ReadingQuestionGroup $group, ?ReadingAnswer $primaryAnswer): array
    {
        $numbers = $this->groupQuestionNumbers($group);
        $start = $numbers !== [] ? min($numbers) : 0;
        $end = $numbers !== [] ? max($numbers) : 0;
        $required = $this->groupQuestionCount($group);
        $selected = $this->countSelected($primaryAnswer?->answer_json);

        return [
            'start' => $start,
            'end' => $end,
            'required' => $required,
            'selected' => $selected,
            'complete' => $this->groupIsComplete($selected, $required),
        ];
    }

    /**
     * @return array<int, bool>
     */
    public function answeredStateByQuestionNumber(ReadingQuestionGroup $group, ?ReadingAnswer $primaryAnswer): array
    {
        $state = $this->resolveGroupSelectionState($group, $primaryAnswer);
        $answered = [];

        if ($state['start'] <= 0 || $state['end'] < $state['start']) {
            return $answered;
        }

        for ($number = $state['start']; $number <= $state['end']; $number++) {
            $answered[$number] = $state['complete'];
        }

        return $answered;
    }

    public function resolvePrimaryQuestion(ReadingQuestionGroup $group): ?ReadingQuestion
    {
        $group->loadMissing('questions');

        return $group->questions
            ->filter(fn (ReadingQuestion $question): bool => (int) $question->question_number > 0)
            ->sortBy('question_number')
            ->first();
    }

    /**
     * @param  Collection<int, ReadingAnswer>  $saved
     */
    public function resolvePrimaryAnswer(
        ReadingQuestionGroup $group,
        Collection $saved,
    ): ?ReadingAnswer {
        $group->loadMissing('questions');

        foreach ($group->questions->sortBy('question_number') as $question) {
            $answer = $saved->get($question->id);

            if ($this->countSelected($answer?->answer_json) > 0) {
                return $answer;
            }
        }

        $primary = $this->resolvePrimaryQuestion($group);

        return $primary !== null ? $saved->get($primary->id) : null;
    }

    public function isQuestionNumberAnsweredInGroup(
        int $questionNumber,
        ReadingQuestionGroup $group,
        ?ReadingAnswer $primaryAnswer,
    ): bool {
        $questionCount = $this->groupQuestionCount($group);
        $selected = $this->countSelected($primaryAnswer?->answer_json);

        if (! $this->groupIsComplete($selected, $questionCount)) {
            return false;
        }

        if ($group->start_question !== null && $group->end_question !== null) {
            return $questionNumber >= (int) $group->start_question
                && $questionNumber <= (int) $group->end_question;
        }

        return in_array($questionNumber, $this->groupQuestionNumbers($group), true);
    }

    /**
     * @return array<int, int> question_number => group_id
     */
    public function reservedQuestionNumbersForPassage(ReadingPassage $passage): array
    {
        $reserved = [];

        foreach ($passage->groups as $group) {
            if (! $this->isMcqMultipleGroup($group)) {
                continue;
            }

            $groupId = (int) $group->id;

            foreach ($this->groupQuestionNumbers($group) as $number) {
                $reserved[$number] = $groupId;
            }
        }

        return $reserved;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, bool>
     */
    public function buildAnsweredQuestionsMap(array $questions): array
    {
        $answered = [];

        foreach ($questions as $number => $entry) {
            $answered[(int) $number] = (bool) ($entry['answered'] ?? false);
        }

        ksort($answered, SORT_NUMERIC);

        return $answered;
    }
}
