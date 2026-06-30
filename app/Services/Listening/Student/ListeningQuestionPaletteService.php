<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\QuestionPaletteItemData;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use Illuminate\Support\Collection;

class ListeningQuestionPaletteService
{
    public function __construct(
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningNavigationService $navigation,
        private readonly ListeningMultipleAnswerCountingService $multipleAnswerCounting,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(ListeningAttempt $attempt): array
    {
        $attempt->loadMissing(['test.questions.group', 'test.questionGroups.section', 'answers']);
        $answerMap = $this->answers->keyedByQuestionId($attempt);
        $currentQuestion = (int) ($attempt->current_question_number ?: 1);
        $items = [];
        $processedMultipleAnswerGroups = [];

        foreach ($attempt->test?->questions()->where('is_active', true)->orderBy('question_number')->get() ?? [] as $question) {
            /** @var ListeningQuestion $question */
            $group = $question->group;

            if ($this->multipleAnswerCounting->isMultipleAnswerQuestion($question) && $group !== null) {
                $groupId = (int) $group->id;

                if (isset($processedMultipleAnswerGroups[$groupId])) {
                    continue;
                }

                $processedMultipleAnswerGroups[$groupId] = true;
                array_push($items, ...$this->buildMultipleAnswerGroupItems(
                    $group,
                    $question,
                    $attempt,
                    $currentQuestion,
                    $answerMap,
                ));

                continue;
            }

            $items[] = $this->buildItem(
                $question,
                $answerMap->get($question->id),
                $attempt,
                $currentQuestion,
                $answerMap,
            )->toArray();
        }

        foreach ($attempt->test?->questionGroups()->where('is_active', true)->orderBy('start_question_number')->get() ?? [] as $group) {
            /** @var ListeningQuestionGroup $group */
            if (! $this->multipleAnswerCounting->isMultipleAnswerGroup($group)) {
                continue;
            }

            if (isset($processedMultipleAnswerGroups[(int) $group->id])) {
                continue;
            }

            $anchorQuestion = $group->questions()->where('is_active', true)->orderBy('question_number')->first();

            if ($anchorQuestion === null) {
                continue;
            }

            array_push($items, ...$this->buildMultipleAnswerGroupItems(
                $group,
                $anchorQuestion,
                $attempt,
                $currentQuestion,
                $answerMap,
            ));
        }

        usort($items, fn (array $left, array $right): int => ($left['question_number'] ?? 0) <=> ($right['question_number'] ?? 0));

        return $items;
    }

    public function buildItem(
        ListeningQuestion $question,
        ?ListeningAttemptAnswer $answer,
        ListeningAttempt $attempt,
        ?int $currentQuestionNumber = null,
        ?Collection $answerMap = null,
    ): QuestionPaletteItemData {
        $current = $currentQuestionNumber ?? (int) ($attempt->current_question_number ?: 1);
        $meta = is_array($answer?->meta) ? $answer->meta : [];
        $isFlagged = ($meta['is_flagged'] ?? false) === true;
        $isAnswered = $this->resolveIsAnswered($question, $answerMap);
        $isCurrent = (int) $question->question_number === $current;
        $sectionNumber = (int) ($question->section?->section_number
            ?? $this->navigation->sectionForQuestionNumber((int) $question->question_number));

        return new QuestionPaletteItemData(
            questionId: $question->id,
            questionNumber: (int) $question->question_number,
            sectionNumber: $sectionNumber,
            status: $this->getStatus($question, $answer, $attempt, $current, $isAnswered, $isFlagged),
            isAnswered: $isAnswered,
            isFlagged: $isFlagged,
            isCurrent: $isCurrent,
        );
    }

    public function getStatus(
        ListeningQuestion $question,
        ?ListeningAttemptAnswer $answer,
        ListeningAttempt $attempt,
        ?int $currentQuestionNumber = null,
        ?bool $isAnswered = null,
        ?bool $isFlagged = null,
    ): string {
        $current = $currentQuestionNumber ?? (int) ($attempt->current_question_number ?: 1);

        if ((int) $question->question_number === $current) {
            return 'current';
        }

        $meta = is_array($answer?->meta) ? $answer->meta : [];
        $flagged = $isFlagged ?? (($meta['is_flagged'] ?? false) === true);

        if ($flagged) {
            return 'flagged';
        }

        $answered = $isAnswered ?? ($answer?->answer_status === ListeningAnswerStatus::Answered);

        if ($answered) {
            return 'answered';
        }

        return 'unanswered';
    }

    public function countAnswered(ListeningAttempt $attempt): int
    {
        return $this->multipleAnswerCounting->countAnsweredQuestions($attempt);
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswer>  $answerMap
     * @return list<array<string, mixed>>
     */
    private function buildMultipleAnswerGroupItems(
        ListeningQuestionGroup $group,
        ListeningQuestion $anchorQuestion,
        ListeningAttempt $attempt,
        int $currentQuestion,
        Collection $answerMap,
    ): array {
        $items = [];
        $groupQuestions = $group->questions()->where('is_active', true)->get();
        $primary = $this->multipleAnswerCounting->resolvePrimaryAnswer($group, $answerMap, $groupQuestions);
        $sectionNumber = (int) ($group->section?->section_number
            ?? $anchorQuestion->section?->section_number
            ?? $this->navigation->sectionForQuestionNumber((int) $group->start_question_number));

        foreach ($this->multipleAnswerCounting->groupQuestionNumbers($group) as $questionNumber) {
            $question = $groupQuestions->firstWhere('question_number', $questionNumber) ?? $anchorQuestion;
            $answer = $groupQuestions->contains('question_number', $questionNumber)
                ? $answerMap->get($question->id)
                : null;
            $isAnswered = $this->multipleAnswerCounting->isQuestionNumberAnsweredInGroup(
                $questionNumber,
                $group,
                $primary,
            );
            $meta = is_array($answer?->meta) ? $answer->meta : [];
            $isFlagged = ($meta['is_flagged'] ?? false) === true;
            $isCurrent = $questionNumber === $currentQuestion;
            $status = $isCurrent
                ? 'current'
                : ($isFlagged ? 'flagged' : ($isAnswered ? 'answered' : 'unanswered'));

            $items[] = (new QuestionPaletteItemData(
                questionId: (int) $question->id,
                questionNumber: $questionNumber,
                sectionNumber: $sectionNumber,
                status: $status,
                isAnswered: $isAnswered,
                isFlagged: $isFlagged,
                isCurrent: $isCurrent,
            ))->toArray();
        }

        return $items;
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswer>|null  $answerMap
     */
    private function resolveIsAnswered(ListeningQuestion $question, ?Collection $answerMap): bool
    {
        if ($answerMap === null) {
            return false;
        }

        if (! $this->multipleAnswerCounting->isMultipleAnswerQuestion($question) || $question->group === null) {
            $answer = $answerMap->get($question->id);

            return $answer?->answer_status === ListeningAnswerStatus::Answered;
        }

        $group = $question->group;
        $groupQuestions = $group->questions->where('is_active', true);
        $primary = $this->multipleAnswerCounting->resolvePrimaryAnswer($group, $answerMap, $groupQuestions);

        return $this->multipleAnswerCounting->isQuestionNumberAnsweredInGroup(
            (int) $question->question_number,
            $group,
            $primary,
        );
    }

    public function countUnanswered(ListeningAttempt $attempt): int
    {
        return max(0, (int) $attempt->total_questions - $this->countAnswered($attempt));
    }

    public function countFlagged(ListeningAttempt $attempt): int
    {
        return (int) $attempt->answers()
            ->get()
            ->filter(function (ListeningAttemptAnswer $answer): bool {
                $meta = is_array($answer->meta) ? $answer->meta : [];

                return ($meta['is_flagged'] ?? false) === true;
            })
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<int, list<array<string, mixed>>>
     */
    public function groupBySection(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $section = (int) ($item['section_number'] ?? 1);
            $grouped[$section][] = $item;
        }

        ksort($grouped);

        return $grouped;
    }
}
