<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningEvaluationType: string
{
    use ListeningEnum;

    case System = 'system';
    case AdminRecheck = 'admin_recheck';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::AdminRecheck => 'Admin Recheck',
        };
    }
}
