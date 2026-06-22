<?php

declare(strict_types=1);

namespace App\Enums\Course;

use App\Enums\Concerns\EnumHelpers;

enum ResourceType: string
{
    use EnumHelpers;

    case Pdf = 'pdf';
    case Link = 'link';
    case Audio = 'audio';
    case Video = 'video';
    case Doc = 'doc';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Link => 'External link',
            self::Audio => 'Audio',
            self::Video => 'Video file',
            self::Doc => 'Document',
        };
    }
}
