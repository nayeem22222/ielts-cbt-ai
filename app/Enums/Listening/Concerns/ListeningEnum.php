<?php

declare(strict_types=1);

namespace App\Enums\Listening\Concerns;

use App\Enums\Concerns\EnumHelpers;

/**
 * Base helpers for Listening backed enums.
 *
 * @mixin \BackedEnum
 */
trait ListeningEnum
{
    use EnumHelpers;

    /**
     * Backing value (spec "value()" — PHP reserves the `value` property on backed enums).
     */
    public function backedValue(): string|int
    {
        return $this->value;
    }

    /**
     * @return array<string|int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
