<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningAnswerStatus: string
{
    use ListeningEnum;

    case Unanswered = 'unanswered';
    case Answered = 'answered';
    case Flagged = 'flagged';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return match ($this) {
            self::Unanswered => 'Unanswered',
            self::Answered => 'Answered',
            self::Flagged => 'Flagged',
            self::Reviewed => 'Reviewed',
        };
    }
}
