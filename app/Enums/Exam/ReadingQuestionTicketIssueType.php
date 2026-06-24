<?php

declare(strict_types=1);

namespace App\Enums\Exam;

enum ReadingQuestionTicketIssueType: string
{
    case WrongAnswer = 'wrong_answer';
    case QuestionProblem = 'question_problem';
    case Typo = 'typo';
    case PassageProblem = 'passage_problem';
    case ExplanationProblem = 'explanation_problem';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WrongAnswer => 'Wrong Answer',
            self::QuestionProblem => 'Question Problem',
            self::Typo => 'Typo',
            self::PassageProblem => 'Passage Problem',
            self::ExplanationProblem => 'Explanation Problem',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
