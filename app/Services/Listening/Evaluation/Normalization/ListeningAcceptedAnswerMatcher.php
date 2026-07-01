<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\AcceptedAnswerMatchData;
use App\DTOs\Listening\Evaluation\Normalization\NormalizedListeningAnswerData;
use App\Models\Listening\ListeningQuestion;
use App\Support\Listening\Evaluation\ListeningMatchReason;

class ListeningAcceptedAnswerMatcher
{
    public function __construct(
        private readonly ListeningNormalizationPipeline $pipeline,
        private readonly ListeningRegexAnswerMatcher $regex,
        private readonly ListeningPluralNormalizer $plural,
        private readonly ListeningSpellingVariantNormalizer $spelling,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $correctAnswers
     * @param  list<array<string, mixed>>  $acceptedAnswers
     */
    public function match(
        mixed $studentAnswer,
        array $correctAnswers,
        array $acceptedAnswers,
        ListeningQuestion $question,
    ): AcceptedAnswerMatchData {
        $targetType = $this->targetType($correctAnswers, $acceptedAnswers);
        $student = $this->pipeline->normalize($this->coerceType($studentAnswer, $targetType), $question);
        $rawStudentValues = $this->rawValues($studentAnswer);
        $normalizedCorrect = $this->pipeline->normalizeCorrectAnswer($correctAnswers, $question);
        $normalizedAccepted = $this->pipeline->normalizeAcceptedAnswers($acceptedAnswers, $question);
        $normalizationSteps = array_merge($student->steps, $this->pipeline->getSteps());

        $allRawAnswers = array_merge($correctAnswers, $acceptedAnswers);

        foreach ($allRawAnswers as $answer) {
            $raw = trim((string) ($answer['value'] ?? ''));

            if ($raw !== '' && in_array($raw, $rawStudentValues, true)) {
                return $this->matched($student, $normalizedCorrect, $raw, (string) ($answer['type'] ?? 'text'), ListeningMatchReason::EXACT_MATCH, $normalizationSteps);
            }
        }

        $correctMatch = $this->matchNormalized($student, $normalizedCorrect, $question);

        if ($correctMatch !== null) {
            return $this->matched($student, $normalizedCorrect, $correctMatch, 'text', ListeningMatchReason::NORMALIZED_MATCH, $normalizationSteps);
        }

        $acceptedMatch = $this->matchNormalized($student, $normalizedAccepted, $question);

        if ($acceptedMatch !== null) {
            return $this->matched($student, $normalizedCorrect, $acceptedMatch, 'text', ListeningMatchReason::ACCEPTED_ANSWER_MATCH, $normalizationSteps);
        }

        foreach ($acceptedAnswers as $answer) {
            $pattern = (string) ($answer['value'] ?? '');

            if ($this->regex->isRegexAnswer($answer) && $student->primary() !== null && $this->regex->match($pattern, $student->primary())) {
                return $this->matched($student, $normalizedCorrect, $pattern, 'regex', ListeningMatchReason::ACCEPTED_ANSWER_MATCH, $normalizationSteps);
            }
        }

        $pluralMatch = $this->matchPlural($student, array_merge($normalizedCorrect, $normalizedAccepted), $question);

        if ($pluralMatch !== null) {
            return $this->matched($student, $normalizedCorrect, $pluralMatch, 'text', ListeningMatchReason::ACCEPTED_ANSWER_MATCH, $normalizationSteps);
        }

        if ($this->spelling->enabled()) {
            $spellingMatch = $this->matchSpelling($student, array_merge($correctAnswers, $acceptedAnswers), $question);

            if ($spellingMatch !== null) {
                return $this->matched($student, $normalizedCorrect, $spellingMatch, 'text', ListeningMatchReason::ACCEPTED_ANSWER_MATCH, $normalizationSteps);
            }
        }

        return new AcceptedAnswerMatchData(
            matched: false,
            matchedValue: null,
            matchedType: null,
            matchReason: ListeningMatchReason::INCORRECT_ANSWER,
            normalizedStudentAnswer: $student,
            normalizedCorrectAnswers: $this->values($normalizedCorrect),
            normalizationSteps: $normalizationSteps,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $correctAnswers
     */
    public function matchSet(
        mixed $studentAnswer,
        array $correctAnswers,
        ListeningQuestion $question,
        bool $orderSensitive,
    ): AcceptedAnswerMatchData {
        $student = $this->pipeline->normalize($studentAnswer, $question);
        $correct = $this->pipeline->normalizeCorrectAnswer($correctAnswers, $question);
        $studentValues = $student->values;
        $correctValues = $this->values($correct);

        if (! $orderSensitive) {
            sort($studentValues);
            sort($correctValues);
        }

        $matched = $studentValues === $correctValues;

        return new AcceptedAnswerMatchData(
            matched: $matched,
            matchedValue: $matched ? implode(',', $correctValues) : null,
            matchedType: 'set',
            matchReason: $matched ? ListeningMatchReason::EXACT_MATCH : ListeningMatchReason::INCORRECT_ANSWER,
            normalizedStudentAnswer: $student,
            normalizedCorrectAnswers: $correctValues,
            normalizationSteps: array_merge($student->steps, $this->pipeline->getSteps()),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return list<string>
     */
    private function values(array $answers): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $answer): string => trim((string) ($answer['value'] ?? '')),
            $answers,
        ), fn (string $value): bool => $value !== '')));
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function matchNormalized(NormalizedListeningAnswerData $student, array $answers, ListeningQuestion $question): ?string
    {
        $studentValues = $student->values;
        $answerValues = $this->values($answers);
        $orderSensitive = (bool) ($question->order_sensitive ?? false);

        if (count($studentValues) > 1 || count($answerValues) > 1) {
            $left = $studentValues;
            $right = $answerValues;

            if (! $orderSensitive) {
                sort($left);
                sort($right);
            }

            return $left === $right ? implode(',', $answerValues) : null;
        }

        foreach ($answerValues as $answer) {
            if (($student->primary() ?? '') === $answer) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function matchPlural(NormalizedListeningAnswerData $student, array $answers, ListeningQuestion $question): ?string
    {
        $studentValue = $student->primary();

        if ($studentValue === null) {
            return null;
        }

        foreach ($this->values($answers) as $answer) {
            if (in_array($studentValue, $this->plural->variants($answer, $question), true)) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function matchSpelling(NormalizedListeningAnswerData $student, array $answers, ListeningQuestion $question): ?string
    {
        $studentValue = $student->primary();

        if ($studentValue === null) {
            return null;
        }

        foreach ($answers as $answer) {
            $normalized = $this->pipeline->normalize([$answer], $question);

            if ($studentValue === $normalized->primary()) {
                return (string) ($answer['value'] ?? '');
            }
        }

        return null;
    }

    private function matched(
        NormalizedListeningAnswerData $student,
        array $normalizedCorrect,
        string $matchedValue,
        string $matchedType,
        string $reason,
        array $steps,
    ): AcceptedAnswerMatchData {
        return new AcceptedAnswerMatchData(
            matched: true,
            matchedValue: $matchedValue,
            matchedType: $matchedType,
            matchReason: $reason,
            normalizedStudentAnswer: $student,
            normalizedCorrectAnswers: $this->values($normalizedCorrect),
            normalizationSteps: $steps,
        );
    }

    /**
     * @return list<string>
     */
    private function rawValues(mixed $answer): array
    {
        if ($answer === null || $answer === '') {
            return [];
        }

        if (is_scalar($answer)) {
            return [trim((string) $answer)];
        }

        if (! is_array($answer)) {
            return [];
        }

        if (array_is_list($answer)) {
            return array_values(array_filter(array_map(
                fn (mixed $item): string => is_array($item) ? trim((string) ($item['value'] ?? '')) : trim((string) $item),
                $answer,
            )));
        }

        return [trim((string) ($answer['value'] ?? ''))];
    }

    /**
     * @param  list<array<string, mixed>>  $correctAnswers
     * @param  list<array<string, mixed>>  $acceptedAnswers
     */
    private function targetType(array $correctAnswers, array $acceptedAnswers): string
    {
        foreach (array_merge($correctAnswers, $acceptedAnswers) as $answer) {
            $type = (string) ($answer['type'] ?? '');

            if ($type !== '' && $type !== 'regex') {
                return $type;
            }
        }

        return 'text';
    }

    private function coerceType(mixed $answer, string $type): mixed
    {
        if (! is_array($answer) || $type === 'text') {
            return $answer;
        }

        if (array_is_list($answer)) {
            return array_map(function (mixed $item) use ($type): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                if (($item['type'] ?? 'text') === 'text') {
                    $item['type'] = $type;
                }

                return $item;
            }, $answer);
        }

        if (($answer['type'] ?? 'text') === 'text') {
            $answer['type'] = $type;
        }

        return $answer;
    }
}
