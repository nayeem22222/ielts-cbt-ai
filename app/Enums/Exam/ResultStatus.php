<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum ResultStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Computed = 'computed';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Computed => 'Computed',
            self::Published => 'Published',
        };
    }
}
