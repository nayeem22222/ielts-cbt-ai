<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes\Concerns;

trait HandlesLabellingQuestionType
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function normalizeLabellingOptions(array $options, ?string $imagePath = null, ?string $imageAlt = null): array
    {
        $normalized = [
            'image' => [
                'path' => (string) ($options['image']['path'] ?? $imagePath ?? ''),
                'alt' => (string) ($options['image']['alt'] ?? $imageAlt ?? ''),
            ],
            'labels' => [],
            'points' => [],
        ];

        foreach ($options['labels'] ?? [] as $label) {
            if (! is_array($label)) {
                continue;
            }

            $normalized['labels'][] = [
                'key' => strtoupper(trim((string) ($label['key'] ?? ''))),
                'text' => trim((string) ($label['text'] ?? '')),
            ];
        }

        foreach ($options['points'] ?? [] as $point) {
            if (! is_array($point)) {
                continue;
            }

            $normalized['points'][] = [
                'number' => (int) ($point['number'] ?? 0),
                'x' => (float) ($point['x'] ?? 0),
                'y' => (float) ($point['y'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    protected function validateLabellingOptions(array $options, ?string $imagePath = null): array
    {
        $errors = array_merge(
            $this->validateImageRequirement($imagePath, $options),
            $this->validateOptionKeys($options['labels'] ?? [], 1),
        );

        $points = $options['points'] ?? [];

        if ($points === []) {
            $errors[] = 'At least one labelling point is required.';
        }

        $min = (float) config('listening.question_types.labelling.min_coordinate', 0);
        $max = (float) config('listening.question_types.labelling.max_coordinate', 100);

        foreach ($points as $point) {
            $x = (float) ($point['x'] ?? -1);
            $y = (float) ($point['y'] ?? -1);

            if ($x < $min || $x > $max || $y < $min || $y > $max) {
                $errors[] = 'Point coordinates must be between '.$min.' and '.$max.'.';
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $correctAnswer
     * @param  list<array<string, mixed>>  $labels
     * @return list<string>
     */
    protected function validateLabellingAnswer(array $correctAnswer, array $labels, string $answerType = 'map_label'): array
    {
        $labelKeys = array_map('strtoupper', $this->optionKeysFromList($labels));
        $errors = $this->validateCorrectAnswerPresence($correctAnswer);

        if ($errors !== []) {
            return $errors;
        }

        foreach ($correctAnswer as $answer) {
            $value = strtoupper(trim((string) ($answer['value'] ?? '')));

            if (! in_array($value, $labelKeys, true)) {
                $errors[] = "Answer \"{$value}\" is not a valid label key.";
            }
        }

        return $errors;
    }
}
