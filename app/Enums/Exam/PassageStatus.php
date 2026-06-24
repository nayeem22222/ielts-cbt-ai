<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum PassageStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Hidden => 'Hidden',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Published => 'green',
            self::Hidden => 'neutral',
            self::Draft => 'amber',
        };
    }
}
