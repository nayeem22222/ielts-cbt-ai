<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation\Normalization;

final readonly class NormalizationStepData
{
    public function __construct(
        public string $step,
        public mixed $before,
        public mixed $after,
    ) {}

    /**
     * @return array{step: string, before: mixed, after: mixed}
     */
    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'before' => $this->before,
            'after' => $this->after,
        ];
    }
}
