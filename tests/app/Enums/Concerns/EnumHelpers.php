<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Shared helpers for backed enums.
 *
 * @mixin \BackedEnum
 */
trait EnumHelpers
{
    /**
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string|int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->name;
        }

        return $options;
    }
}
