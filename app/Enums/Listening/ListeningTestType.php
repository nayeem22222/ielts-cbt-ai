<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningTestType: string
{
    use ListeningEnum;

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
