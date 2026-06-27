<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningTranscriptSourceType: string
{
    use ListeningEnum;

    case Manual = 'manual';
    case Imported = 'imported';
    case AiGenerated = 'ai_generated';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Imported => 'Imported',
            self::AiGenerated => 'AI Generated',
        };
    }
}
