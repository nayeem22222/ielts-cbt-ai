<?php

declare(strict_types=1);

namespace App\DTOs\Listening\QuestionTypes;

readonly class MatchingPairData
{
    public function __construct(
        public string $itemKey,
        public string $choiceKey,
    ) {}

    /**
     * @return array{item_key: string, value: string, type: string}
     */
    public function toAnswerArray(): array
    {
        return [
            'item_key' => $this->itemKey,
            'value' => $this->choiceKey,
            'type' => 'matching',
        ];
    }
}
