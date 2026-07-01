<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningRegexAnswerMatcher
{
    public function isRegexAnswer(mixed $answer): bool
    {
        $value = is_array($answer) ? (string) ($answer['value'] ?? '') : (string) $answer;

        return ($answer['type'] ?? null) === 'regex'
            || (strlen($value) >= 2 && str_starts_with($value, '/') && strrpos($value, '/') > 0);
    }

    public function validateRegex(string $pattern): bool
    {
        if (! (bool) config('listening.normalization.regex_answers.enabled', true)) {
            return false;
        }

        if ($pattern === '' || strlen($pattern) > (int) config('listening.normalization.regex_answers.max_pattern_length', 255)) {
            return false;
        }

        if (! str_starts_with($pattern, '/') || strrpos($pattern, '/') === 0) {
            return false;
        }

        if ($this->looksCatastrophic($pattern)) {
            return false;
        }

        set_error_handler(static fn (): bool => true);
        $result = preg_match($pattern, '');
        restore_error_handler();

        return $result !== false;
    }

    public function match(string $pattern, string $value): bool
    {
        if (! $this->validateRegex($pattern)) {
            return false;
        }

        set_error_handler(static fn (): bool => true);
        $result = preg_match($pattern, $value);
        restore_error_handler();

        return $result === 1;
    }

    private function looksCatastrophic(string $pattern): bool
    {
        return preg_match('/\([^)]*[+*][^)]*\)[+*{]/', $pattern) === 1;
    }
}
