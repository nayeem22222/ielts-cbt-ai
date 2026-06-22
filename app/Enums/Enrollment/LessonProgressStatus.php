<?php

declare(strict_types=1);

namespace App\Enums\Enrollment;

use App\Enums\Concerns\EnumHelpers;

enum LessonProgressStatus: string
{
    use EnumHelpers;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
        };
    }
}
