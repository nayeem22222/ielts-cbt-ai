<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes\Concerns;

use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

trait HandlesMcqStyleOptions
{
    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    protected function normalizeMcqOptions(array $options): array
    {
        return array_values(array_map(fn (array $option): array => [
            'key' => strtoupper(trim((string) ($option['key'] ?? ''))),
            'text' => trim((string) ($option['text'] ?? '')),
            'is_correct' => (bool) ($option['is_correct'] ?? false),
        ], $options));
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  list<array<string, mixed>>  $correctAnswer
     * @return list<string>
     */
    protected function validateMcqCorrectAnswer(array $options, array $correctAnswer, bool $exactlyOne = true): array
    {
        $keys = $this->optionKeysFromList($options);
        $values = $this->answerValues($correctAnswer);

        if ($values === []) {
            return $this->validateCorrectAnswerPresence($correctAnswer);
        }

        if ($exactlyOne && count($values) !== 1) {
            return ['Exactly one correct answer is required for MCQ.'];
        }

        foreach ($values as $value) {
            if (! in_array(strtoupper($value), array_map('strtoupper', $keys), true)) {
                return ["Correct answer \"{$value}\" does not match any option key."];
            }
        }

        return [];
    }
}
