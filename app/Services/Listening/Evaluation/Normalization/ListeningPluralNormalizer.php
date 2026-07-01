<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Normalization;

use App\Models\Listening\ListeningQuestion;
use Illuminate\Support\Str;

class ListeningPluralNormalizer
{
    /**
     * @return list<string>
     */
    public function variants(string $value, ListeningQuestion $question): array
    {
        if (! $this->allowed($question)) {
            return [$value];
        }

        $variants = [$value];

        if (Str::endsWith($value, 'ies')) {
            $variants[] = Str::substr($value, 0, -3).'y';
        } elseif (Str::endsWith($value, 'es')) {
            $variants[] = Str::substr($value, 0, -2);
        } elseif (Str::endsWith($value, 's') && ! Str::endsWith($value, 'ss')) {
            $variants[] = Str::substr($value, 0, -1);
        } else {
            $variants[] = $value.'s';
        }

        return array_values(array_unique($variants));
    }

    public function allowed(ListeningQuestion $question): bool
    {
        return $question->allow_plural
            ?? (bool) config('listening.normalization.allow_plural_default', true);
    }
}
