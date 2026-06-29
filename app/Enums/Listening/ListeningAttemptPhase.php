<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningAttemptPhase: string
{
    use ListeningEnum;

    case Instructions = 'instructions';
    case Listening = 'listening';
    case Transfer = 'transfer';
    case Submitting = 'submitting';
    case Submitted = 'submitted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Instructions => 'Instructions',
            self::Listening => 'Listening Time',
            self::Transfer => 'Transfer Time',
            self::Submitting => 'Submitting',
            self::Submitted => 'Submitted',
            self::Expired => 'Expired',
        };
    }
}
