<?php

declare(strict_types=1);

namespace App\Enums\Course;

use App\Enums\Concerns\EnumHelpers;

enum ExamType: string
{
    use EnumHelpers;

    case Academic = 'academic';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic',
            self::General => 'General Training',
        };
    }
}
