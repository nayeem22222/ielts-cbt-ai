<?php

declare(strict_types=1);

namespace App\Enums\Commerce;

use App\Enums\Concerns\EnumHelpers;

enum IeltsModule: string
{
    use EnumHelpers;

    case Reading = 'reading';
    case Listening = 'listening';
    case Writing = 'writing';
    case Speaking = 'speaking';

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading',
            self::Listening => 'Listening',
            self::Writing => 'Writing',
            self::Speaking => 'Speaking',
        };
    }
}
