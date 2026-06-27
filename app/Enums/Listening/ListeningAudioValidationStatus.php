<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningAudioValidationStatus: string
{
    use ListeningEnum;

    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Valid => 'Valid',
            self::Invalid => 'Invalid',
        };
    }
}
