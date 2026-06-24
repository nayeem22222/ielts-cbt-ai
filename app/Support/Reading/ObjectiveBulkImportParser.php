<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Enums\Exam\OfficialReadingQuestionType;

final class ObjectiveBulkImportParser
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function parse(string $text, OfficialReadingQuestionType $type): array
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

            if ($type === OfficialReadingQuestionType::TrueFalseNotGiven
                || $type === OfficialReadingQuestionType::YesNoNotGiven) {
                $rows[] = [
                    'question_number' => $questionNumber,
                    'prompt' => $parts[1],
                    'correct_answer' => strtoupper(str_replace(' ', '_', $parts[2])),
                ];

                continue;
            }

            if ($type === OfficialReadingQuestionType::MultipleChoiceSingle) {
                $correct = strtoupper(array_pop($parts));
                $prompt = $parts[1];
                $optionLabels = array_slice($parts, 2);

                $rows[] = [
                    'question_number' => $questionNumber,
                    'prompt' => $prompt,
                    'options' => self::labelsToOptions($optionLabels),
                    'correct_answer' => $correct,
                ];

                continue;
            }

            if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
                $correctRaw = strtoupper(array_pop($parts));
                $prompt = $parts[1];
                $optionLabels = array_slice($parts, 2);

                $rows[] = [
                    'question_number' => $questionNumber,
                    'prompt' => $prompt,
                    'options' => self::labelsToOptions($optionLabels),
                    'correct_answers' => array_values(array_filter(array_map(
                        'trim',
                        explode(',', $correctRaw),
                    ))),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>  $labels
     * @return list<array{option_key: string, option_label: string}>
     */
    private static function labelsToOptions(array $labels): array
    {
        $options = [];

        foreach ($labels as $index => $label) {
            if ($label === '') {
                continue;
            }

            $options[] = [
                'option_key' => self::optionKeyForIndex($index),
                'option_label' => $label,
            ];
        }

        return $options;
    }

    public static function optionKeyForIndex(int $index): string
    {
        $index = max(0, $index);
        $key = '';

        do {
            $key = chr(65 + ($index % 26)).$key;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $key;
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $lines = preg_split('/\R/', $text) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));
    }
}
