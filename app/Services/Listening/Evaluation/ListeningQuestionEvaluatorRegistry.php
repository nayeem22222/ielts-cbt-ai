<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Evaluators\BaseListeningQuestionEvaluator;
use App\Services\Listening\Evaluation\Evaluators\CompletionAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\LabellingAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\LetterAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\MatchingAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\MultipleAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\ShortAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\TextAnswerEvaluator;
use App\Support\Listening\Evaluation\ListeningMatchReason;
use Throwable;

class ListeningQuestionEvaluatorRegistry
{
    public function __construct(
        private readonly TextAnswerEvaluator $textEvaluator,
        private readonly LetterAnswerEvaluator $letterEvaluator,
        private readonly MultipleAnswerEvaluator $multipleAnswerEvaluator,
        private readonly MatchingAnswerEvaluator $matchingEvaluator,
        private readonly LabellingAnswerEvaluator $labellingEvaluator,
        private readonly CompletionAnswerEvaluator $completionEvaluator,
        private readonly ShortAnswerEvaluator $shortAnswerEvaluator,
    ) {}

    public function evaluate(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        try {
            return $this->evaluatorFor($question)->evaluate($attemptAnswer, $question);
        } catch (Throwable $exception) {
            return new ListeningAnswerEvaluationResultData(
                attemptAnswerId: $attemptAnswer->id,
                questionId: $question->id,
                questionNumber: (int) $question->question_number,
                questionType: $question->question_type->value,
                studentAnswerSnapshot: $attemptAnswer->student_answer,
                normalizedStudentAnswer: null,
                correctAnswerSnapshot: is_array($question->correct_answer) ? $question->correct_answer : [],
                acceptedAnswersSnapshot: is_array($question->accepted_answers) ? $question->accepted_answers : [],
                matchedAnswer: null,
                isCorrect: false,
                marksAvailable: (float) ($question->marks ?? 1),
                marksAwarded: 0.0,
                matchStatus: ListeningMatchStatus::ManualReview,
                matchReason: ListeningMatchReason::MANUAL_REVIEW_REQUIRED,
                evaluatorMeta: ['exception' => $exception->getMessage()],
            );
        }
    }

    public function evaluatorFor(ListeningQuestion $question): BaseListeningQuestionEvaluator
    {
        return match ($question->question_type) {
            ListeningQuestionType::MCQ => $this->letterEvaluator,
            ListeningQuestionType::MultipleAnswer => $this->multipleAnswerEvaluator,
            ListeningQuestionType::Matching => $this->matchingEvaluator,
            ListeningQuestionType::MapLabelling,
            ListeningQuestionType::PlanLabelling,
            ListeningQuestionType::DiagramLabelling => $this->labellingEvaluator,
            ListeningQuestionType::ShortAnswer => $this->shortAnswerEvaluator,
            ListeningQuestionType::FormCompletion,
            ListeningQuestionType::NoteCompletion,
            ListeningQuestionType::TableCompletion,
            ListeningQuestionType::FlowchartCompletion,
            ListeningQuestionType::SentenceCompletion,
            ListeningQuestionType::SummaryCompletion => $this->completionEvaluator,
            default => $this->textEvaluator,
        };
    }
}
