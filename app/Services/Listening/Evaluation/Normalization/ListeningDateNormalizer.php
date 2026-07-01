<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningDateNormalizer
{
    /** @var list<string> */
    private array $months = [
        'january', 'february', 'march', 'april', 'may', 'june',
        'july', 'august', 'september', 'october', 'november', 'december',
    ];

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, callable $audit): string
    {
        if (! (bool) config('listening.normalization.dates.enabled', true)) {
            return $value;
        }

        $before = $value;
        $value = (string) preg_replace('/\b(\d{1,2})(st|nd|rd|th)\b/i', '$1', $value);

        if ($value !== $before) {
            $audit('normalize_date_ordinal_suffix', $before, $value);
        }

        $monthPattern = implode('|', $this->months);

        if (preg_match('/\b('.$monthPattern.')\s+(\d{1,2})\b/i', $value, $matches) === 1) {
            $after = (int) $matches[2].' '.mb_strtolower($matches[1]);
            $audit('normalize_date_month_first', $value, $after);

            return $after;
        }

        if (preg_match('/\b(\d{1,2})\s+('.$monthPattern.')\b/i', $value, $matches) === 1) {
            $after = (int) $matches[1].' '.mb_strtolower($matches[2]);
            $audit('normalize_date_day_first', $value, $after);

            return $after;
        }

        if ((bool) config('listening.normalization.dates.ambiguous_numeric_dates', false)
            && preg_match('/^(\d{1,2})[\/.-](\d{1,2})$/', $value, $matches) === 1) {
            $after = (int) $matches[1].'/'.(int) $matches[2];
            $audit('normalize_numeric_date', $value, $after);

            return $after;
        }

        return $value;
    }
}
