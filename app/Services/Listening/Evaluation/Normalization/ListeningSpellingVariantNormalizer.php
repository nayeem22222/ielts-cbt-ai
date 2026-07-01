<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

class ListeningSpellingVariantNormalizer
{
    /**
     * @param  callable(string, mixed, mixed): void  $audit
     */
    public function normalize(string $value, callable $audit): string
    {
        if (! $this->enabled()) {
            return $value;
        }

        $map = (array) config('listening.normalization.british_american_spelling.map', []);
        $after = $value;

        foreach ($map as $from => $to) {
            $after = (string) preg_replace('/\b'.preg_quote((string) $from, '/').'\b/iu', (string) $to, $after);
        }

        if ($after !== $value) {
            $audit('spelling_variant', $value, $after);
        }

        return $after;
    }

    public function enabled(): bool
    {
        return (bool) config('listening.normalization.british_american_spelling.enabled', false);
    }
}
