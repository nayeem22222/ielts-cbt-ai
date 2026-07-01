<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningTimeNormalizer
{
    public function __construct(
        private readonly ListeningNumberNormalizer $numbers,
    ) {}

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, callable $audit): string
    {
        if (! (bool) config('listening.normalization.times.enabled', true)) {
            return $value;
        }

        $value = trim(mb_strtolower($value));

        if (preg_match('/^([a-z\s-]+)\s*(am|pm)$/i', $value, $matches) === 1) {
            $number = $this->numbers->wordsToNumber(trim($matches[1]));

            if ($number !== null) {
                $before = $value;
                $value = $number.' '.$matches[2];
                $audit('time_words_to_number', $before, $value);
            }
        }

        if (preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)?$/i', $value, $matches) !== 1) {
            return $value;
        }

        $hour = (int) $matches[1];
        $minute = (int) ($matches[2] ?? 0);
        $period = mb_strtolower((string) ($matches[3] ?? ''));

        if ($period === 'pm' && $hour < 12) {
            $hour += 12;
        }

        if ($period === 'am' && $hour === 12) {
            $hour = 0;
        }

        if ($hour > 23 || $minute > 59) {
            return $value;
        }

        $after = sprintf('%02d:%02d', $hour, $minute);
        $audit('normalize_time', $value, $after);

        return $after;
    }
}
