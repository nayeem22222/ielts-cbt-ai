<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\QuestionPaletteItemData;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;

class ListeningQuestionPaletteService
{
    public function __construct(
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningNavigationService $navigation,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(ListeningAttempt $attempt): array
    {
        $attempt->loadMissing(['test.questions', 'answers']);
        $answerMap = $this->answers->keyedByQuestionId($attempt);
        $currentQuestion = (int) ($attempt->current_question_number ?: 1);
        $items = [];

        foreach ($attempt->test?->questions()->where('is_active', true)->orderBy('question_number')->get() ?? [] as $question) {
            /** @var ListeningQuestion $question */
            $answer = $answerMap->get($question->id);
            $items[] = $this->buildItem($question, $answer, $attempt, $currentQuestion)->toArray();
        }

        return $items;
    }

    public function buildItem(
        ListeningQuestion $question,
        ?ListeningAttemptAnswer $answer,
        ListeningAttempt $attempt,
        ?int $currentQuestionNumber = null,
    ): QuestionPaletteItemData {
        $current = $currentQuestionNumber ?? (int) ($attempt->current_question_number ?: 1);
        $meta = is_array($answer?->meta) ? $answer->meta : [];
        $isFlagged = ($meta['is_flagged'] ?? false) === true;
        $isAnswered = $answer?->answer_status === ListeningAnswerStatus::Answered;
        $isCurrent = (int) $question->question_number === $current;
        $sectionNumber = (int) ($question->section?->section_number
            ?? $this->navigation->sectionForQuestionNumber((int) $question->question_number));

        return new QuestionPaletteItemData(
            questionId: $question->id,
            questionNumber: (int) $question->question_number,
            sectionNumber: $sectionNumber,
            status: $this->getStatus($question, $answer, $attempt, $current),
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
    ): string {
        $current = $currentQuestionNumber ?? (int) ($attempt->current_question_number ?: 1);

        if ((int) $question->question_number === $current) {
            return 'current';
        }

        $meta = is_array($answer?->meta) ? $answer->meta : [];

        if (($meta['is_flagged'] ?? false) === true) {
            return 'flagged';
        }

        if ($answer?->answer_status === ListeningAnswerStatus::Answered) {
            return 'answered';
        }

        return 'unanswered';
    }

    public function countAnswered(ListeningAttempt $attempt): int
    {
        return $this->answers->countAnswered($attempt);
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
