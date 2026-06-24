<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;

class ReadingResultService
{
    public function __construct(
        private readonly ReadingTestRendererService $renderer,
        private readonly ReadingEvaluationService $evaluation,
        private readonly ReadingReviewAnalyticsService $analytics,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildResultPageData(ReadingAttempt $attempt): array
    {
        $this->evaluation->assertResultAvailable($attempt);

        if ($attempt->evaluated_at === null && $attempt->status === TestAttemptStatus::Submitted) {
            $this->evaluation->evaluateAttempt($attempt);
            $attempt = $attempt->fresh(['test']);
        }

        $attempt->loadMissing(['test', 'answers.question.group.passage', 'answers.question.correctAnswers', 'answers.question.options']);
        $summary = $this->evaluation->buildAttemptSummary($attempt);
        $reviewParts = $this->buildReviewParts($attempt);

        return [
            'attempt' => $attempt,
            'test' => $attempt->test,
            'summary' => $summary,
            'part_analytics' => $this->analytics->buildPartAnalytics($reviewParts),
            'question_map' => $this->analytics->buildQuestionMap($reviewParts),
            'insights' => $this->analytics->buildWeakAreas($reviewParts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReviewPageData(ReadingAttempt $attempt): array
    {
        $result = $this->buildResultPageData($attempt);
        $attempt = $result['attempt'];
        $test = $this->renderer->loadForRenderer($attempt->test);
        $parts = $this->buildReviewParts($attempt, $test);

        return array_merge($result, [
            'parts' => $parts,
            'review_passages' => $this->analytics->buildReviewPassages($test),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildReviewParts(ReadingAttempt $attempt, $test = null): array
    {
        $test = $test ?? $this->renderer->loadForRenderer($attempt->test);
        $savedAnswers = $attempt->answers->keyBy('question_id');
        $items = [];

        foreach ($test->passages as $passage) {
            $partItems = [];

            foreach ($this->renderer->questionsForPassage($passage) as $question) {
                $partItems[] = $this->buildReviewItem($question, $savedAnswers->get($question->id));
            }

            $items[] = [
                'passage_id' => $passage->id,
                'part_number' => $passage->part_number,
                'title' => $passage->title,
                'questions' => $partItems,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewItem(ReadingQuestion $question, $answer): array
    {
        $question->loadMissing(['correctAnswers', 'options', 'group.groupOptions', 'group.passage']);

        if ($answer === null) {
            $outcome = $this->evaluation->markUnanswered(
                $question,
                (float) ($question->marks ?: 1),
                $question->correctAnswers->first(),
            );
        } else {
            $outcome = $this->evaluation->evaluateAnswer($answer, $question);

            if ($answer->evaluation_json !== null) {
                $outcome['evaluation_json'] = array_merge(
                    is_array($answer->evaluation_json) ? $answer->evaluation_json : [],
                    ['persisted' => true],
                );
            }
        }

        return array_merge($outcome, [
            'flagged' => (bool) ($answer?->flagged),
            'reference_start_offset' => $question->reference_start_offset,
            'reference_end_offset' => $question->reference_end_offset,
            'reference_paragraph' => $question->reference_paragraph ?? $question->paragraph_reference,
        ]);
    }
}
