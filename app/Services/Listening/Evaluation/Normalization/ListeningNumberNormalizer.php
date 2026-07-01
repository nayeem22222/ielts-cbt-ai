<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningNumberNormalizer
{
    /** @var array<string, int> */
    private array $numbers = [
        'zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4,
        'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9,
        'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13,
        'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17,
        'eighteen' => 18, 'nineteen' => 19, 'twenty' => 20, 'thirty' => 30,
        'forty' => 40, 'fifty' => 50, 'sixty' => 60, 'seventy' => 70,
        'eighty' => 80, 'ninety' => 90,
    ];

    /** @var array<string, int> */
    private array $ordinals = [
        'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5,
        'sixth' => 6, 'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10,
        'eleventh' => 11, 'twelfth' => 12, 'thirteenth' => 13, 'fourteenth' => 14,
        'fifteenth' => 15, 'sixteenth' => 16, 'seventeenth' => 17, 'eighteenth' => 18,
        'nineteenth' => 19, 'twentieth' => 20,
    ];

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, callable $audit): string
    {
        $value = $this->normalizeCommaNumber($value, $audit);
        $value = $this->normalizeOrdinal($value, $audit);

        if ((bool) config('listening.normalization.numbers.words_to_numbers', true)) {
            $converted = $this->wordsToNumber($value);

            if ($converted !== null) {
                $audit('words_to_number', $value, (string) $converted);

                return (string) $converted;
            }
        }

        return $this->normalizeDecimal($value, $audit);
    }

    public function wordsToNumber(string $value): ?int
    {
        $tokens = preg_split('/[\s-]+/', str_replace(' and ', ' ', mb_strtolower(trim($value)))) ?: [];
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        $total = 0;
        $current = 0;

        foreach ($tokens as $token) {
            if (isset($this->ordinals[$token])) {
                $current += $this->ordinals[$token];
                continue;
            }

            if (isset($this->numbers[$token])) {
                $current += $this->numbers[$token];
                continue;
            }

            if ($token === 'hundred') {
                $current = max(1, $current) * 100;
                continue;
            }

            if ($token === 'thousand') {
                $total += max(1, $current) * 1000;
                $current = 0;
                continue;
            }

            return null;
        }

        return $total + $current;
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalizeOrdinal(string $value, callable $audit): string
    {
        $after = (string) preg_replace('/\b(\d+)(st|nd|rd|th)\b/i', '$1', $value);

        if (isset($this->ordinals[$value])) {
            $after = (string) $this->ordinals[$value];
        }

        if ($after !== $value) {
            $audit('normalize_ordinal', $value, $after);
        }

        return $after;
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalizeCommaNumber(string $value, callable $audit): string
    {
        $after = (string) preg_replace('/(?<=\d),(?=\d{3}\b)/', '', $value);

        if ($after !== $value) {
            $audit('normalize_comma_number', $value, $after);
        }

        return $after;
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalizeDecimal(string $value, callable $audit): string
    {
        if (is_numeric($value)) {
            if (! str_contains($value, '.')) {
                return $value;
            }

            $after = rtrim(rtrim((string) ((float) $value), '0'), '.');

            if ($after !== $value) {
                $audit('normalize_decimal', $value, $after);
            }

            return $after;
        }

        return $value;
    }
}
