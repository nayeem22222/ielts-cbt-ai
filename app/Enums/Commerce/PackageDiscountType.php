<?php

declare(strict_types=1);

namespace App\Enums\Commerce;

use App\Enums\Concerns\EnumHelpers;

enum PackageDiscountType: string
{
    use EnumHelpers;

    case None = 'none';
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No discount',
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
