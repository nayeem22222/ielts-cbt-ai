<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use Illuminate\Support\Collection;

class ListeningMultipleAnswerCountingService
{
    public function __construct(
        private readonly ListeningAttemptAnswerRepository $answers,
    ) {}

    public function isMultipleAnswerGroup(?ListeningQuestionGroup $group): bool
    {
        return $group !== null && $group->question_type === ListeningQuestionType::MultipleAnswer;
    }

    public function isMultipleAnswerQuestion(ListeningQuestion $question): bool
    {
        return $question->question_type === ListeningQuestionType::MultipleAnswer;
    }

    public function groupQuestionCount(ListeningQuestionGroup $group): int
    {
        $start = (int) $group->start_question_number;
        $end = (int) $group->end_question_number;

        return max(1, $end - $start + 1);
    }

    /**
     * @return list<int>
     */
    public function groupQuestionNumbers(ListeningQuestionGroup $group): array
    {
        $numbers = [];

        for ($number = (int) $group->start_question_number; $number <= (int) $group->end_question_number; $number++) {
            $numbers[] = $number;
        }

        return $numbers;
    }

    /**
     * @param  list<array<string, mixed>>  $normalized
     */
    public function countSelectedFromNormalized(array $normalized): int
    {
        $count = 0;

        foreach ($normalized as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (trim((string) ($item['value'] ?? '')) !== '') {
                $count++;
            }
        }

        return $count;
    }

    public function countSelectedLetters(?ListeningAttemptAnswer $answer): int
    {
        if ($answer === null) {
            return 0;
        }

        $normalized = is_array($answer->normalized_answer) ? $answer->normalized_answer : [];

        if ($normalized === [] && is_array($answer->student_answer)) {
            $normalized = $answer->student_answer;
        }

        return $this->countSelectedFromNormalized($normalized);
    }

    public function groupIsComplete(int $selectedCount, int $questionCount): bool
    {
        return $selectedCount === $questionCount;
    }

    /**
     * @param  Collection<int, ListeningQuestion>  $groupQuestions
     * @param  Collection<int, ListeningAttemptAnswer>  $answerMap
     */
    public function resolvePrimaryAnswer(
        ListeningQuestionGroup $group,
        Collection $answerMap,
        Collection $groupQuestions,
    ): ?ListeningAttemptAnswer {
        $sorted = $groupQuestions->sortBy('question_number')->values();

        foreach ($sorted as $question) {
            $answer = $answerMap->get($question->id);

            if ($this->countSelectedLetters($answer) > 0) {
                return $answer;
            }
        }

        foreach ($this->groupQuestionNumbers($group) as $number) {
            $answer = $answerMap->first(
                fn (ListeningAttemptAnswer $row): bool => (int) $row->question_number === $number
                    && $this->countSelectedLetters($row) > 0,
            );

            if ($answer !== null) {
                return $answer;
            }
        }

        $first = $sorted->first();

        return $first !== null ? $answerMap->get($first->id) : null;
    }

    public function isQuestionNumberAnsweredInGroup(
        int $questionNumber,
        ListeningQuestionGroup $group,
        ?ListeningAttemptAnswer $primaryAnswer,
    ): bool {
        $questionCount = $this->groupQuestionCount($group);
        $selected = $this->countSelectedLetters($primaryAnswer);

        if (! $this->groupIsComplete($selected, $questionCount)) {
            return false;
        }

        return $questionNumber >= (int) $group->start_question_number
            && $questionNumber <= (int) $group->end_question_number;
    }

    public function countAnsweredQuestions(ListeningAttempt $attempt): int
    {
        $attempt->loadMissing(['test.questions.group', 'test.questionGroups', 'answers']);
        $answerMap = $this->answers->keyedByQuestionId($attempt);
        $count = 0;
        $processedGroupIds = [];

        foreach ($attempt->test?->questionGroups()->where('is_active', true)->orderBy('start_question_number')->get() ?? [] as $group) {
            /** @var ListeningQuestionGroup $group */
            if (! $this->isMultipleAnswerGroup($group)) {
                continue;
            }

            $groupId = (int) $group->id;
            $processedGroupIds[$groupId] = true;
            $groupQuestions = $group->questions()->where('is_active', true)->get();
            $primary = $this->resolvePrimaryAnswer($group, $answerMap, $groupQuestions);
            $selected = $this->countSelectedLetters($primary);
            $questionCount = $this->groupQuestionCount($group);

            if ($this->groupIsComplete($selected, $questionCount)) {
                $count += $questionCount;
            }
        }

        foreach ($attempt->test?->questions()->where('is_active', true)->orderBy('question_number')->get() ?? [] as $question) {
            /** @var ListeningQuestion $question */
            if ($this->isMultipleAnswerQuestion($question) && $question->group !== null) {
                continue;
            }

            $answer = $answerMap->get($question->id);

            if ($answer?->answer_status === ListeningAnswerStatus::Answered) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $normalizedAnswer
     */
    public function syncGroupAnswerStatuses(
        ListeningAttempt $attempt,
        ListeningQuestion $primaryQuestion,
        array $normalizedAnswer,
    ): void {
        $primaryQuestion->loadMissing('group.questions');
        $group = $primaryQuestion->group;

        if (! $this->isMultipleAnswerGroup($group)) {
            return;
        }

        $questionCount = $this->groupQuestionCount($group);
        $selected = $this->countSelectedFromNormalized($normalizedAnswer);
        $complete = $this->groupIsComplete($selected, $questionCount);
        $questionsByNumber = $group->questions()->where('is_active', true)->get()->keyBy('question_number');

        foreach ($this->groupQuestionNumbers($group) as $number) {
            $question = $questionsByNumber->get($number);

            if ($question === null) {
                continue;
            }

            /** @var ListeningQuestion $question */
            $row = $this->answers->findForAttemptQuestion($attempt, $question->id);

            if ($row === null) {
                continue;
            }

            $meta = is_array($row->meta) ? $row->meta : [];
            $isFlagged = ($meta['is_flagged'] ?? false) === true;
            $slotAnswered = $complete;
            $isPrimary = (int) $question->id === (int) $primaryQuestion->id;

            $payload = [
                'answer_status' => $this->resolveSlotStatus($slotAnswered, $isFlagged),
                'answered_at' => $slotAnswered ? ($row->answered_at ?? now()) : null,
            ];

            if ($isPrimary) {
                $payload['student_answer'] = $selected > 0 ? $normalizedAnswer : null;
                $payload['normalized_answer'] = $selected > 0 ? $normalizedAnswer : null;
            } else {
                $payload['student_answer'] = null;
                $payload['normalized_answer'] = null;
            }

            $row->fill($payload)->save();
        }
    }

    private function resolveSlotStatus(bool $slotAnswered, bool $isFlagged): ListeningAnswerStatus
    {
        if ($slotAnswered) {
            return ListeningAnswerStatus::Answered;
        }

        if ($isFlagged) {
            return ListeningAnswerStatus::Flagged;
        }

        return ListeningAnswerStatus::Unanswered;
    }
}
