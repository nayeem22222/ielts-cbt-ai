<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningMatchStatus: string
{
    use ListeningEnum;

    case Correct = 'correct';
    case Incorrect = 'incorrect';
    case Partial = 'partial';
    case Unanswered = 'unanswered';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Correct => 'Correct',
            self::Incorrect => 'Incorrect',
            self::Partial => 'Partial',
            self::Unanswered => 'Unanswered',
            self::ManualReview => 'Manual Review',
        };
    }
}
