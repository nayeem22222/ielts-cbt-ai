<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningResultStatus: string
{
    use ListeningEnum;

    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
            self::Hidden => 'Hidden',
        };
    }
}
