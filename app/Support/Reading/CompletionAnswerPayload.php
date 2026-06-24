<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingCorrectAnswer;

final class CompletionAnswerPayload
{
    /**
     * @return list<string>
     */
    public static function answers(?ReadingCorrectAnswer $correct): array
    {
        if (! $correct instanceof ReadingCorrectAnswer) {
            return [];
        }

        $json = $correct->answer_json;

        if (is_array($json) && isset($json['answers']) && is_array($json['answers'])) {
            return array_values(array_map('strval', $json['answers']));
        }

        if (is_array($json)) {
            return array_values(array_map('strval', $json));
        }

        return $correct->answer ? [(string) $correct->answer] : [];
    }

    /**
     * @return list<string>
     */
    public static function alternatives(?ReadingCorrectAnswer $correct): array
    {
        $answers = self::answers($correct);
        $primary = (string) ($correct?->answer ?? ($answers[0] ?? ''));

        return array_values(array_filter(
            $answers,
            fn (string $value): bool => $value !== $primary,
        ));
    }

    public static function caseSensitive(?ReadingCorrectAnswer $correct): bool
    {
        if (! $correct instanceof ReadingCorrectAnswer) {
            return false;
        }

        $json = $correct->answer_json;

        return is_array($json) && (bool) ($json['case_sensitive'] ?? false);
    }

    public static function wordLimit(?ReadingCorrectAnswer $correct): ?string
    {
        if (! $correct instanceof ReadingCorrectAnswer) {
            return null;
        }

        $json = $correct->answer_json;

        return is_array($json) ? ($json['word_limit'] ?? null) : null;
    }
}
