<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation;

final readonly class ListeningNormalizedAnswerData
{
    /**
     * @param  list<string>  $values
     * @param  list<string>  $steps
     */
    public function __construct(
        public array $values,
        public array $steps = [],
        public ?string $format = null,
    ) {}

    public function primary(): ?string
    {
        return $this->values[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->values === [] || ($this->primary() ?? '') === '';
    }
}
