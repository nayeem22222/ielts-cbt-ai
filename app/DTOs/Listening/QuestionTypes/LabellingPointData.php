<?php

declare(strict_types=1);

namespace App\DTOs\Listening\QuestionTypes;

readonly class LabellingPointData
{
    public function __construct(
        public int $number,
        public float $x,
        public float $y,
    ) {}

    /**
     * @return array{number: int, x: float, y: float}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
