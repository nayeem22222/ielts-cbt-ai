<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningReviewVisibility: string
{
    use ListeningEnum;

    case Hidden = 'hidden';
    case StudentVisible = 'student_visible';
    case AdminOnly = 'admin_only';

    public function label(): string
    {
        return match ($this) {
            self::Hidden => 'Hidden',
            self::StudentVisible => 'Student Visible',
            self::AdminOnly => 'Admin Only',
        };
    }
}
