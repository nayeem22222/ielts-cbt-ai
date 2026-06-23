<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum ScoringMethod: string
{
    use EnumHelpers;

    case Auto = 'auto';
    case Ai = 'ai';
    case Teacher = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatic',
            self::Ai => 'AI assisted',
            self::Teacher => 'Teacher review',
        };
    }
}
