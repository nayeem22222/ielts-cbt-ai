<?php

declare(strict_types=1);

namespace App\Enums\Enrollment;

use App\Enums\Concerns\EnumHelpers;

enum StudentPackageStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }
}
