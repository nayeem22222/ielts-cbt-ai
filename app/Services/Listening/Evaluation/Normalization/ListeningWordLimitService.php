<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\WordLimitResultData;
use App\Models\Listening\ListeningQuestion;

class ListeningWordLimitService
{
    public function check(mixed $answer, ListeningQuestion $question): WordLimitResultData
    {
        $limit = $question->word_limit !== null ? (int) $question->word_limit : null;
        $text = $this->stringValue($answer);
        $tokens = $this->tokenize($text);
        $count = count($tokens);

        return new WordLimitResultData(
            exceeded: $limit !== null && $limit > 0 && $count > $limit,
            wordCount: $count,
            limit: $limit,
            tokens: $tokens,
        );
    }

    public function countWords(string $value): int
    {
        return count($this->tokenize($value));
    }

    /**
     * @return list<string>
     */
    public function tokenize(string $value): array
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return [];
        }

        if (! (bool) config('listening.normalization.word_limit.hyphenated_as_one', true)) {
            $value = str_replace(['-', '–', '—'], ' ', $value);
        }

        $tokens = preg_split('/\s+/u', $value) ?: [];

        return array_values(array_filter($tokens, fn (string $token): bool => trim($token) !== ''));
    }

    public function exceedsLimit(mixed $answer, ListeningQuestion $question): bool
    {
        return $this->check($answer, $question)->exceeded;
    }

    private function stringValue(mixed $answer): string
    {
        if ($answer === null) {
            return '';
        }

        if (is_scalar($answer)) {
            return (string) $answer;
        }

        if (is_array($answer)) {
            if (array_is_list($answer)) {
                return implode(' ', array_map(fn (mixed $item): string => $this->stringValue($item), $answer));
            }

            return (string) ($answer['value'] ?? '');
        }

        return '';
    }
}
