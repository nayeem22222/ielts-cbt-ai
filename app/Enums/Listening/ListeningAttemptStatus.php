<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningAttemptStatus: string
{
    use ListeningEnum;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoSubmitted = 'auto_submitted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Submitted => 'Submitted',
            self::AutoSubmitted => 'Auto Submitted',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }
}
