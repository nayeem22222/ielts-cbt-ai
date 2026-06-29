<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use Illuminate\Support\Facades\DB;

class FlagListeningQuestionAction
{
    public function __construct(
        private readonly ListeningAttemptAnswerRepository $answers,
    ) {}

    public function execute(ListeningAttempt $attempt, ListeningQuestion $question, bool $flagged): void
    {
        if ((int) $question->listening_test_id !== (int) $attempt->listening_test_id) {
            abort(422, 'Question does not belong to this test.');
        }

        DB::transaction(function () use ($attempt, $question, $flagged): void {
            $row = $this->answers->findForAttemptQuestion($attempt, $question->id);
            $meta = is_array($row?->meta) ? $row->meta : [];
            $meta['is_flagged'] = $flagged;

            if ($row === null) {
                $this->answers->createRowsForQuestions($attempt, collect([$question]));
                $row = $this->answers->findForAttemptQuestion($attempt, $question->id);
            }

            $row?->fill([
                'meta' => $meta,
                'answer_status' => $flagged && ($row->student_answer === null || $row->student_answer === [])
                    ? ListeningAnswerStatus::Flagged
                    : ($row->student_answer !== null && $row->student_answer !== []
                        ? ListeningAnswerStatus::Answered
                        : ListeningAnswerStatus::Unanswered),
            ])->save();
        });
    }
}
