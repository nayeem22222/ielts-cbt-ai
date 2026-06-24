<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Enums\Exam\OfficialReadingQuestionType;

final class MatchingBulkImportParser
{
    /**
     * @return list<array{option_key: string, option_label: string}>
     */
    public static function parseOptions(string $text): array
    {
        $rows = [];

        foreach (self::lines($text) as $line) {
            if (str_contains($line, '|')) {
                [$key, $label] = array_map('trim', explode('|', $line, 2));
            } elseif (str_contains($line, "\t")) {
                [$key, $label] = array_map('trim', explode("\t", $line, 2));
            } else {
                $key = trim($line);
                $label = '';
            }

            if ($key === '') {
                continue;
            }

            $rows[] = [
                'option_key' => $key,
                'option_label' => $label,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{question_number: int, prompt: string, correct_answer: string, paragraph_reference: ?string}>
     */
    public static function parseQuestions(string $text, OfficialReadingQuestionType $type): array
    {
        $rows = [];

        foreach (self::lines($text) as $line) {
            $parts = str_contains($line, '|')
                ? array_map('trim', explode('|', $line))
                : array_map('trim', explode("\t", $line));

            if (count($parts) < 3 || ! is_numeric($parts[0])) {
                continue;
            }

            $questionNumber = (int) $parts[0];
            $prompt = $parts[1];
            $correctAnswer = $parts[2];
            $paragraphReference = null;

            if ($type->requiresParagraphReference()) {
                $paragraphReference = self::extractParagraphReference($prompt);
            }

            $rows[] = [
                'question_number' => $questionNumber,
                'prompt' => $prompt,
                'correct_answer' => $correctAnswer,
                'paragraph_reference' => $paragraphReference,
            ];
        }

        return $rows;
    }

  /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $lines = preg_split('/\R/', $text) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));
    }

    private static function extractParagraphReference(string $prompt): ?string
    {
        if (preg_match('/paragraph\s+([A-Z])/i', $prompt, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
