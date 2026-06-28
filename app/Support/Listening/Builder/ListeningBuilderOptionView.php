<?php

declare(strict_types=1);

namespace App\Support\Listening\Builder;

final class ListeningBuilderOptionView
{
    public function __construct(
        public int $id,
        public string $option_key,
        public string $option_label,
        public int $sort_order = 0,
    ) {}
}
