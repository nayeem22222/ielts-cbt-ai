<?php

declare(strict_types=1);

namespace App\Services\Exam\Scoring;

use App\Enums\Exam\ReadingQuestionType;
use App\Models\Question;
use App\Models\QuestionCorrectAnswer;
use App\Models\StudentAnswer;

class ReadingAnswerMatcher
{
    public function score(Question $question, ?StudentAnswer $studentAnswer): QuestionScoreOutcome
    {
        $maxScore = (float) ($question->marks ?: 1);
        $correct = $question->correctAnswer;
        $type = $question->type;

        if ($correct === null) {
            return new QuestionScoreOutcome(
                isCorrect: false,
                scoreAwarded: 0,
                maxScore: $maxScore,
                partialRatio: 0,
                studentResponse: $this->extractStudentResponse($studentAnswer),
                expectedResponse: null,
                feedback: 'No correct answer configured.',
            );
        }

        return match ($type) {
            ReadingQuestionType::MultipleChoiceMultiple => $this->scoreMultipleChoiceMultiple($question, $studentAnswer, $correct, $maxScore),
            ReadingQuestionType::MultipleChoiceSingle,
            ReadingQuestionType::TrueFalseNg,
            ReadingQuestionType::YesNoNg,
            ReadingQuestionType::MatchingHeadings,
            ReadingQuestionType::MatchingInformation,
            ReadingQuestionType::MatchingFeatures,
            ReadingQuestionType::MatchingSentenceEndings => $this->scoreSingleSelection($question, $studentAnswer, $correct, $maxScore),
            default => $this->scoreTextAnswer($question, $studentAnswer, $correct, $maxScore),
        };
    }

    private function scoreSingleSelection(
        Question $question,
        ?StudentAnswer $studentAnswer,
        QuestionCorrectAnswer $correct,
        float $maxScore,
    ): QuestionScoreOutcome {
        $student = $this->canonicalizeSelection($question, $this->extractStudentResponse($studentAnswer));
        $expected = $this->canonicalizeSelection($question, $this->expectedValue($correct));

        $isCorrect = $student !== '' && $student === $expected;

        return new QuestionScoreOutcome(
            isCorrect: $isCorrect,
            scoreAwarded: $isCorrect ? $maxScore : 0,
            maxScore: $maxScore,
            partialRatio: $isCorrect ? 1 : 0,
            studentResponse: $student,
            expectedResponse: $expected,
            feedback: $isCorrect ? 'Correct' : 'Incorrect',
        );
    }

    private function scoreMultipleChoiceMultiple(
        Question $question,
        ?StudentAnswer $studentAnswer,
        QuestionCorrectAnswer $correct,
        float $maxScore,
    ): QuestionScoreOutcome {
        $expected = collect($this->expectedValues($correct))
            ->map(fn (string $value): string => $this->canonicalizeSelection($question, $value))
            ->filter()
            ->unique()
            ->values();

        $student = collect($this->studentValues($studentAnswer))
            ->map(fn (string $value): string => $this->canonicalizeSelection($question, $value))
            ->filter()
            ->unique()
            ->values();

        if ($expected->isEmpty()) {
            return new QuestionScoreOutcome(false, 0, $maxScore, 0, $student->implode(', '), null, 'No correct answer configured.');
        }

        $matched = $expected->intersect($student)->count();
        $extraWrong = $student->diff($expected)->count();
        $partialRatio = max(0, min(1, ($matched - $extraWrong) / $expected->count()));
        $scoreAwarded = round($maxScore * $partialRatio, 2);
        $isCorrect = $partialRatio >= 1;

        return new QuestionScoreOutcome(
            isCorrect: $isCorrect,
            scoreAwarded: $scoreAwarded,
            maxScore: $maxScore,
            partialRatio: round($partialRatio, 4),
            studentResponse: $student->implode(', '),
            expectedResponse: $expected->implode(', '),
            feedback: $isCorrect
                ? 'Correct'
                : ($partialRatio > 0 ? 'Partially correct' : 'Incorrect'),
        );
    }

    private function scoreTextAnswer(
        Question $question,
        ?StudentAnswer $studentAnswer,
        QuestionCorrectAnswer $correct,
        float $maxScore,
    ): QuestionScoreOutcome {
        $student = $this->normalizeText($this->extractStudentResponse($studentAnswer));
        $acceptable = collect($this->expectedValues($correct))
            ->map(fn (string $value): string => $this->normalizeText($value))
            ->filter()
            ->unique()
            ->values();

        if ($student === '') {
            return new QuestionScoreOutcome(false, 0, $maxScore, 0, null, $acceptable->first(), 'Unanswered');
        }

        $isCorrect = $acceptable->contains($student);

        return new QuestionScoreOutcome(
            isCorrect: $isCorrect,
            scoreAwarded: $isCorrect ? $maxScore : 0,
            maxScore: $maxScore,
            partialRatio: $isCorrect ? 1 : 0,
            studentResponse: $student,
            expectedResponse: $acceptable->implode(' / '),
            feedback: $isCorrect ? 'Correct' : 'Incorrect',
        );
    }

    /**
     * @return list<string>
     */
    private function expectedValues(QuestionCorrectAnswer $correct): array
    {
        if (is_array($correct->answer_json) && $correct->answer_json !== []) {
            return collect($correct->answer_json)
                ->flatMap(function (mixed $value): array {
                    if (is_array($value)) {
                        return array_map('strval', $value);
                    }

                    return [(string) $value];
                })
                ->all();
        }

        if (filled($correct->answer_value)) {
            return [(string) $correct->answer_value];
        }

        return [];
    }

    private function expectedValue(QuestionCorrectAnswer $correct): string
    {
        return $this->expectedValues($correct)[0] ?? '';
    }

    /**
     * @return list<string>
     */
    private function studentValues(?StudentAnswer $studentAnswer): array
    {
        if ($studentAnswer === null) {
            return [];
        }

        if (is_array($studentAnswer->selected_options) && $studentAnswer->selected_options !== []) {
            return array_map('strval', $studentAnswer->selected_options);
        }

        $text = trim((string) ($studentAnswer->answer_text ?? ''));

        if ($text === '') {
            return [];
        }

        if (str_contains($text, ',')) {
            return array_map('trim', explode(',', $text));
        }

        return [$text];
    }

    private function extractStudentResponse(?StudentAnswer $studentAnswer): ?string
    {
        if ($studentAnswer === null) {
            return null;
        }

        if (filled($studentAnswer->answer_text)) {
            return (string) $studentAnswer->answer_text;
        }

        if (is_array($studentAnswer->selected_options) && $studentAnswer->selected_options !== []) {
            return implode(', ', $studentAnswer->selected_options);
        }

        return null;
    }

    private function canonicalizeSelection(Question $question, ?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $normalized = $this->normalizeText($value);

        foreach ($question->options as $option) {
            $label = $this->normalizeText($option->label);
            $text = $this->normalizeText($option->option_text);

            if ($normalized === $label || $normalized === $text) {
                return $text !== '' ? $text : $label;
            }
        }

        return $this->normalizeSpecialAnswers($normalized);
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = html_entity_decode(strip_tags($value));
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $this->normalizeSpecialAnswers($value);
    }

    private function normalizeSpecialAnswers(string $value): string
    {
        return match ($value) {
            't', 'true' => 'true',
            'f', 'false' => 'false',
            'ng', 'not given', 'notgiven' => 'not given',
            'y', 'yes' => 'yes',
            'n', 'no' => 'no',
            default => $value,
        };
    }
}
