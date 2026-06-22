<?php

declare(strict_types=1);

namespace App\Enums\Commerce;

use App\Enums\Concerns\EnumHelpers;

enum PackageStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }
}
