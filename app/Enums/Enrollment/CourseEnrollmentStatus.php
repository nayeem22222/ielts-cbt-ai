<?php

declare(strict_types=1);

namespace App\Enums\Enrollment;

use App\Enums\Concerns\EnumHelpers;

enum CourseEnrollmentStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
        };
    }
}
