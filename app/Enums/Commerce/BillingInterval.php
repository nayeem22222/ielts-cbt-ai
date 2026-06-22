<?php

declare(strict_types=1);

namespace App\Enums\Commerce;

use App\Enums\Concerns\EnumHelpers;

enum BillingInterval: string
{
    use EnumHelpers;

    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
            self::Lifetime => 'Lifetime',
        };
    }
}
