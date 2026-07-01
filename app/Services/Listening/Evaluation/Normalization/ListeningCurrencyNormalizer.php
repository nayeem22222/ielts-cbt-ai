<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningCurrencyNormalizer
{
    public function __construct(
        private readonly ListeningNumberNormalizer $numbers,
    ) {}

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, callable $audit): string
    {
        if (! (bool) config('listening.normalization.currency.enabled', true)) {
            return $value;
        }

        $before = $value;
        $value = trim(mb_strtolower($value));
        $currency = null;

        if (str_starts_with($value, '$')) {
            $currency = 'dollar';
            $value = trim(mb_substr($value, 1));
        } elseif (str_starts_with($value, '£')) {
            $currency = 'pound';
            $value = trim(mb_substr($value, 1));
        } elseif (preg_match('/\b(dollars?|pounds?)\b/', $value, $matches) === 1) {
            $currency = str_starts_with($matches[1], 'pound') ? 'pound' : 'dollar';
            $value = trim((string) preg_replace('/\b(dollars?|pounds?)\b/', '', $value));
        }

        $number = $this->numbers->normalize($value, $audit);

        if ($currency === null) {
            return $number;
        }

        $after = trim($number.' '.$currency);
        $audit('normalize_currency', $before, $after);

        return $after;
    }
}
