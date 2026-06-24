<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ReadingAttempt;

class ReadingReviewService
{
    public function __construct(
        private readonly ReadingAnswerService $answers,
        private readonly ReadingSubmitService $submit,
        private readonly ReadingTestRendererService $renderer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReviewSummary(ReadingAttempt $attempt): array
    {
        $attempt->loadMissing('test', 'currentQuestion');
        $test = $this->renderer->loadForRenderer($attempt->test);
        $navigator = $this->answers->buildNavigatorStatus($attempt);
        $visited = $this->submit->visitedQuestions($attempt);

        $answered = 0;
        $unanswered = 0;
        $flagged = 0;
        $notVisited = 0;
        $parts = [];

        foreach ($test->passages as $passage) {
            $partQuestions = [];

            foreach ($this->renderer->questionsForPassage($passage) as $question) {
                $number = $question->question_number;
                $nav = $navigator['questions'][$number] ?? [];
                $isAnswered = (bool) ($nav['answered'] ?? false);
                $isFlagged = (bool) ($nav['flagged'] ?? false);
                $isVisited = in_array($number, $visited, true)
                    || $isAnswered
                    || $attempt->current_question_id === $question->id;

                if ($isAnswered) {
                    $answered++;
                } else {
                    $unanswered++;
                }

                if ($isFlagged) {
                    $flagged++;
                }

                if (! $isVisited) {
                    $notVisited++;
                }

                $status = $nav['status'] ?? 'unanswered';
                if (! $isVisited && ! $isAnswered) {
                    $status = 'not-visited';
                }

                $partQuestions[] = [
                    'question_id' => $question->id,
                    'question_number' => $number,
                    'answered' => $isAnswered,
                    'flagged' => $isFlagged,
                    'visited' => $isVisited,
                    'current' => $attempt->current_question_id === $question->id,
                    'status' => $status,
                    'passage_id' => $passage->id,
                ];
            }

            $parts[] = [
                'passage_id' => $passage->id,
                'part_label' => 'Part '.($passage->part_number ?: $passage->sort_order),
                'title' => $passage->title,
                'question_range' => $passage->question_range_label,
                'questions' => $partQuestions,
            ];
        }

        $unansweredNumbers = [];
        $flaggedNumbers = [];

        foreach ($navigator['questions'] as $number => $item) {
            if (! ($item['answered'] ?? false)) {
                $unansweredNumbers[] = (int) $number;
            }
            if ($item['flagged'] ?? false) {
                $flaggedNumbers[] = (int) $number;
            }
        }

        sort($unansweredNumbers);
        sort($flaggedNumbers);

        return [
            'summary' => [
                'total' => $navigator['total_questions'],
                'answered' => $answered,
                'unanswered' => $unanswered,
                'flagged' => $flagged,
                'not_visited' => $notVisited,
            ],
            'parts' => $parts,
            'unanswered_numbers' => $unansweredNumbers,
            'flagged_numbers' => $flaggedNumbers,
            'navigator' => $navigator,
            'visited_questions' => $visited,
        ];
    }
}
