<?php

declare(strict_types=1);

namespace App\Enums\Settings;

use App\Enums\Concerns\EnumHelpers;

enum SettingsGroup: string
{
    use EnumHelpers;

    case General = 'general';
    case Brand = 'brand';
    case Ai = 'ai';
    case Payment = 'payment';
    case Storage = 'storage';
    case Redis = 'redis';
    case Queue = 'queue';
    case Security = 'security';
    case Backup = 'backup';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Brand => 'Brand',
            self::Ai => 'AI',
            self::Payment => 'Payment',
            self::Storage => 'Storage',
            self::Redis => 'Redis',
            self::Queue => 'Queue',
            self::Security => 'Security',
            self::Backup => 'Backup',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::General => '⚙️',
            self::Brand => '🎨',
            self::Ai => '🤖',
            self::Payment => '💳',
            self::Storage => '📦',
            self::Redis => '🔴',
            self::Queue => '📬',
            self::Security => '🔒',
            self::Backup => '💾',
        };
    }

    /**
     * @return list<self>
     */
    public static function tabs(): array
    {
        return self::cases();
    }
}
