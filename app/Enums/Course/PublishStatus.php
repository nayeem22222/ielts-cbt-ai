<?php

declare(strict_types=1);

namespace App\Enums\Course;

use App\Enums\Concerns\EnumHelpers;

enum PublishStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
