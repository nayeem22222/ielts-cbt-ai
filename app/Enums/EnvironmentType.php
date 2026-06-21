<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum EnvironmentType: string
{
    use EnumHelpers;

    case Local = 'local';
    case Staging = 'staging';
    case Production = 'production';
}
