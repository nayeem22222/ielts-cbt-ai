<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

use App\Models\Listening\ListeningQuestion;

class ListeningTextNormalizer
{
    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, ListeningQuestion $question, callable $audit): string
    {
        $value = $this->apply('trim', $value, trim($value), $audit);
        $value = $this->normalizeUnicode($value, $audit);
        $value = $this->apply('normalize_whitespace', $value, (string) preg_replace('/\s+/u', ' ', $value), $audit);

        if (! $this->caseSensitive($question)) {
            $value = $this->apply('lowercase', $value, mb_strtolower($value), $audit);
        }

        $value = $this->normalizeQuotes($value, $audit);

        if ($this->normalizeHyphen($question)) {
            $value = $this->apply('normalize_hyphen', $value, str_replace(['-', '–', '—', '−'], ' ', $value), $audit);
            $value = $this->apply('normalize_whitespace', $value, (string) preg_replace('/\s+/u', ' ', $value), $audit);
        }

        if ($this->ignorePunctuation($question)) {
            $after = (string) preg_replace('/[[:punct:]]+/u', ' ', $value);
            $after = (string) preg_replace('/\s+/u', ' ', trim($after));
            $value = $this->apply('remove_punctuation', $value, $after, $audit);
        }

        if ($this->removeArticles($question)) {
            $articles = (array) config('listening.normalization.articles', ['a', 'an', 'the']);
            $pattern = '/\b('.implode('|', array_map('preg_quote', $articles)).')\b/iu';
            $after = (string) preg_replace($pattern, ' ', $value);
            $after = (string) preg_replace('/\s+/u', ' ', trim($after));
            $value = $this->apply('remove_articles', $value, $after, $audit);
        }

        return trim($value);
    }

    public function caseSensitive(ListeningQuestion $question): bool
    {
        return $question->case_sensitive ?? (bool) config('listening.normalization.case_sensitive_default', false);
    }

    public function ignorePunctuation(ListeningQuestion $question): bool
    {
        return $question->allow_punctuation_variation
            ?? (bool) config('listening.normalization.ignore_punctuation_default', true);
    }

    public function removeArticles(ListeningQuestion $question): bool
    {
        return $question->allow_articles
            ?? (bool) config('listening.normalization.ignore_articles_default', true);
    }

    public function normalizeHyphen(ListeningQuestion $question): bool
    {
        return (bool) config('listening.normalization.normalize_hyphen_default', true);
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    private function normalizeUnicode(string $value, callable $audit): string
    {
        $after = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{00A0}"],
            ["'", "'", '"', '"', ' '],
            $value,
        );

        return $this->apply('normalize_unicode', $value, $after, $audit);
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    private function normalizeQuotes(string $value, callable $audit): string
    {
        $after = str_replace(["'", '"'], '', $value);

        return $this->apply('normalize_apostrophe', $value, $after, $audit);
    }

    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    private function apply(string $step, string $before, string $after, callable $audit): string
    {
        if ($before !== $after) {
            $audit($step, $before, $after);
        }

        return $after;
    }
}
