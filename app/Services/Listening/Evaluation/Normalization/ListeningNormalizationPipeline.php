<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\NormalizationStepData;
use App\DTOs\Listening\Evaluation\Normalization\NormalizedListeningAnswerData;
use App\Models\Listening\ListeningQuestion;

class ListeningNormalizationPipeline
{
    /** @var list<array{step: string, before: mixed, after: mixed}> */
    private array $steps = [];

    public function __construct(
        private readonly ListeningTextNormalizer $text,
        private readonly ListeningNumberNormalizer $number,
        private readonly ListeningDateNormalizer $date,
        private readonly ListeningTimeNormalizer $time,
        private readonly ListeningCurrencyNormalizer $currency,
        private readonly ListeningSpellingVariantNormalizer $spelling,
        private readonly ListeningWordLimitService $wordLimit,
    ) {}

    public function normalize(mixed $answer, ListeningQuestion $question): NormalizedListeningAnswerData
    {
        $this->steps = [];
        $items = $this->toItems($answer);
        $normalizedItems = [];
        $values = [];

        $this->record('preserve_original', $answer, $answer);
        $this->record('safe_conversion', $answer, $items);

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? $question->answer_format?->value ?? 'text');
            $value = trim(strip_tags((string) ($item['value'] ?? '')));

            if ($value === '') {
                continue;
            }

            $normalized = $this->usesFullTextRules($type)
                ? $this->applyTextRules($value, $question)
                : $this->applyBasicRules($value, $question);
            $normalized = $this->usesFullTextRules($type)
                ? $this->spelling->normalize($normalized, $this->audit(...))
                : $normalized;
            $normalized = $this->applyTypeSpecificRules($normalized, $type, $question);

            $normalizedItem = array_merge($item, [
                'value' => $normalized,
                'type' => $type,
            ]);

            if (isset($item['label'])) {
                $normalizedItem['label'] = $this->normalizeLabel((string) $item['label']);
            }

            if (isset($item['item_key'])) {
                $normalizedItem['item_key'] = strtoupper(trim((string) $item['item_key']));
            }

            $normalizedItems[] = $normalizedItem;
            $values[] = $normalized;
        }

        $limit = $this->wordLimit->check($items, $question);
        $this->record('word_limit_check', $limit->tokens, [
            'count' => $limit->wordCount,
            'limit' => $limit->limit,
            'exceeded' => $limit->exceeded,
        ]);

        return new NormalizedListeningAnswerData(
            original: $answer,
            items: $normalizedItems,
            values: array_values(array_unique($values)),
            steps: $this->steps,
            wordLimit: $limit,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $answer
     * @return list<array<string, mixed>>
     */
    public function normalizeCorrectAnswer(array $answer, ListeningQuestion $question): array
    {
        return $this->normalize($answer, $question)->items;
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return list<array<string, mixed>>
     */
    public function normalizeAcceptedAnswers(array $answers, ListeningQuestion $question): array
    {
        return $this->normalize($answers, $question)->items;
    }

    /**
     * @return list<array{step: string, before: mixed, after: mixed}>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function applyTextRules(string $value, ListeningQuestion $question): string
    {
        return $this->text->normalize($value, $question, $this->audit(...));
    }

    public function applyBasicRules(string $value, ListeningQuestion $question): string
    {
        $before = $value;
        $value = trim(strip_tags($value));

        if ($before !== $value) {
            $this->audit('trim', $before, $value);
        }

        $before = $value;
        $value = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{00A0}", '–', '—', '−'], ["'", "'", '"', '"', ' ', '-', '-', '-'], $value);

        if ($before !== $value) {
            $this->audit('normalize_unicode', $before, $value);
        }

        $before = $value;
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        if ($before !== $value) {
            $this->audit('normalize_whitespace', $before, $value);
        }

        if (! ($question->case_sensitive ?? (bool) config('listening.normalization.case_sensitive_default', false))) {
            $before = $value;
            $value = mb_strtolower($value);

            if ($before !== $value) {
                $this->audit('lowercase', $before, $value);
            }
        }

        return $value;
    }

    public function applyTypeSpecificRules(string $value, string $type, ListeningQuestion $question): string
    {
        return match ($type) {
            'number' => $this->number->normalize($value, $this->audit(...)),
            'date' => $this->date->normalize($value, $this->audit(...)),
            'time' => $this->time->normalize($value, $this->audit(...)),
            'currency' => $this->currency->normalize($value, $this->audit(...)),
            'letter', 'matching', 'map_label', 'diagram_label' => $this->normalizeLabel($value),
            default => $value,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toItems(mixed $answer): array
    {
        if ($answer === null || $answer === '' || $answer === []) {
            return [];
        }

        if (is_string($answer)) {
            $decoded = json_decode($answer, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->toItems($decoded);
            }

            return [['value' => $answer, 'type' => 'text']];
        }

        if (! is_array($answer)) {
            return [['value' => is_scalar($answer) ? (string) $answer : '', 'type' => 'text']];
        }

        if (array_is_list($answer)) {
            return array_values(array_filter(array_map(function (mixed $item): ?array {
                if ($item === null || $item === '') {
                    return null;
                }

                if (is_array($item)) {
                    return $item;
                }

                return ['value' => is_scalar($item) ? (string) $item : '', 'type' => 'text'];
            }, $answer)));
        }

        return [$answer];
    }

    private function normalizeLabel(string $value): string
    {
        $before = $value;
        $after = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');

        if ($before !== $after) {
            $this->audit('normalize_label', $before, $after);
        }

        return $after;
    }

    private function usesFullTextRules(string $type): bool
    {
        return in_array($type, ['text', 'regex'], true);
    }

    private function audit(string $step, mixed $before, mixed $after): void
    {
        $this->record($step, $before, $after);
    }

    private function record(string $step, mixed $before, mixed $after): void
    {
        if ($before === $after && ! in_array($step, ['preserve_original', 'word_limit_check'], true)) {
            return;
        }

        $this->steps[] = (new NormalizationStepData($step, $before, $after))->toArray();
    }
}
