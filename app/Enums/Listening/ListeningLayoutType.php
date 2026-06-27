<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningLayoutType: string
{
    use ListeningEnum;

    case Default = 'default';
    case TwoColumn = 'two_column';
    case Table = 'table';
    case Map = 'map';
    case Diagram = 'diagram';
    case Form = 'form';
    case Flowchart = 'flowchart';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::TwoColumn => 'Two Column',
            self::Table => 'Table',
            self::Map => 'Map',
            self::Diagram => 'Diagram',
            self::Form => 'Form',
            self::Flowchart => 'Flowchart',
        };
    }
}
