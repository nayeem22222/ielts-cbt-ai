<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum TestAttemptStatus: string
{
    use EnumHelpers;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
            self::Completed => 'Completed',
        };
    }
}
