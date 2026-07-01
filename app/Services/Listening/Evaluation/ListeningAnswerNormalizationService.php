<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningNormalizedAnswerData;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Normalization\ListeningNormalizationPipeline;
use App\Services\Listening\Evaluation\Normalization\ListeningPluralNormalizer;

class ListeningAnswerNormalizationService
{
    public function __construct(
        private readonly ListeningNormalizationPipeline $pipeline,
        private readonly ListeningPluralNormalizer $plural,
    ) {}

    /**
     * @param  list<array<string, mixed>>|null  $answer
     */
    public function normalize(?array $answer, ListeningQuestion $question, string $mode = 'text'): ListeningNormalizedAnswerData
    {
        $prepared = $this->withMode($answer, $mode);
        $normalized = $this->pipeline->normalize($prepared, $question);

        return new ListeningNormalizedAnswerData(
            values: $normalized->values,
            steps: $normalized->steps,
            format: $mode,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return list<string>
     */
    public function extractValues(array $answers): array
    {
        $values = [];

        foreach ($answers as $item) {
            if (! is_array($item)) {
                $value = trim((string) $item);
            } else {
                $value = trim((string) ($item['value'] ?? ''));
            }

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    public function pluralVariant(string $value): string
    {
        return $this->plural->variants($value, new ListeningQuestion(['allow_plural' => true]))[1] ?? $value;
    }

    public function matchesWithVariants(string $student, string $correct, ListeningQuestion $question): bool
    {
        if ($student === $correct) {
            return true;
        }

        if (! ($question->allow_plural ?? (bool) config('listening.normalization.allow_plural_default', true))) {
            return false;
        }

        return in_array($student, $this->plural->variants($correct, $question), true)
            || in_array($correct, $this->plural->variants($student, $question), true);
    }

    /**
     * @param  list<array<string, mixed>>|null  $answer
     * @return list<array<string, mixed>>|null
     */
    private function withMode(?array $answer, string $mode): ?array
    {
        if ($answer === null) {
            return null;
        }

        return array_map(function (mixed $item) use ($mode): array {
            if (! is_array($item)) {
                return ['value' => (string) $item, 'type' => $mode];
            }

            $item['type'] = $item['type'] ?? $mode;

            return $item;
        }, $answer);
    }
}
