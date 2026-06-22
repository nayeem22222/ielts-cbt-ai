<?php

declare(strict_types=1);

namespace App\Enums\Course;

use App\Enums\Concerns\EnumHelpers;

enum LessonContentType: string
{
    use EnumHelpers;

    case Video = 'video';
    case Text = 'text';
    case Quiz = 'quiz';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Text => 'Text',
            self::Quiz => 'Quiz',
            self::Live => 'Live session',
        };
    }
}
