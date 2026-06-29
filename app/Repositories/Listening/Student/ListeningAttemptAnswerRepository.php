<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use Illuminate\Support\Collection;

class ListeningAttemptAnswerRepository
{
    /**
     * @param  list<int>  $questionIds
     */
    public function createRowsForQuestions(ListeningAttempt $attempt, Collection $questions): void
    {
        foreach ($questions as $question) {
            /** @var ListeningQuestion $question */
            ListeningAttemptAnswer::query()->firstOrCreate(
                [
                    'listening_attempt_id' => $attempt->id,
                    'listening_question_id' => $question->id,
                ],
                [
                    'listening_test_id' => $attempt->listening_test_id,
                    'question_number' => $question->question_number,
                    'student_answer' => null,
                    'normalized_answer' => null,
                    'answer_status' => ListeningAnswerStatus::Unanswered,
                ],
            );
        }
    }

    public function findForAttemptQuestion(ListeningAttempt $attempt, int $questionId): ?ListeningAttemptAnswer
    {
        return ListeningAttemptAnswer::query()
            ->where('listening_attempt_id', $attempt->id)
            ->where('listening_question_id', $questionId)
            ->first();
    }

    public function countAnswered(ListeningAttempt $attempt): int
    {
        return (int) ListeningAttemptAnswer::query()
            ->where('listening_attempt_id', $attempt->id)
            ->where('answer_status', ListeningAnswerStatus::Answered)
            ->count();
    }

    /**
     * @return Collection<int, ListeningAttemptAnswer>
     */
    public function keyedByQuestionId(ListeningAttempt $attempt): Collection
    {
        return $attempt->answers()->get()->keyBy('listening_question_id');
    }
}
