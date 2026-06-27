<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningTranscriptVisibility: string
{
    use ListeningEnum;

    case Hidden = 'hidden';
    case AdminOnly = 'admin_only';
    case ReviewVisible = 'review_visible';

    public function label(): string
    {
        return match ($this) {
            self::Hidden => 'Hidden',
            self::AdminOnly => 'Admin Only',
            self::ReviewVisible => 'Review Visible',
        };
    }
}
