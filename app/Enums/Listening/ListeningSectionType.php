<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningSectionType: string
{
    use ListeningEnum;

    case Conversation = 'conversation';
    case Monologue = 'monologue';
    case AcademicDiscussion = 'academic_discussion';
    case Lecture = 'lecture';

    public function label(): string
    {
        return match ($this) {
            self::Conversation => 'Conversation',
            self::Monologue => 'Monologue',
            self::AcademicDiscussion => 'Academic Discussion',
            self::Lecture => 'Lecture',
        };
    }
}
