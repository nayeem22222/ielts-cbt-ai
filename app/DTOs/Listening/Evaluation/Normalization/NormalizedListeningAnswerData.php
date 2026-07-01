<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation\Normalization;

final readonly class NormalizedListeningAnswerData
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $values
     * @param  list<array{step: string, before: mixed, after: mixed}>  $steps
     */
    public function __construct(
        public mixed $original,
        public array $items,
        public array $values,
        public array $steps = [],
        public ?WordLimitResultData $wordLimit = null,
    ) {}

    public function primary(): ?string
    {
        return $this->values[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->values === [] || trim((string) ($this->primary() ?? '')) === '';
    }
}
