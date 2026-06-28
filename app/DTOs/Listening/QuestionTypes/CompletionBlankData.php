<?php

declare(strict_types=1);

namespace App\DTOs\Listening\QuestionTypes;

readonly class CompletionBlankData
{
    public function __construct(
        public int $number,
        public ?string $label = null,
        public ?string $placeholder = null,
    ) {}

    /**
     * @return array{number: int, label?: string, placeholder?: string}
     */
    public function toArray(): array
    {
        $data = ['number' => $this->number];

        if ($this->label !== null) {
            $data['label'] = $this->label;
        }

        if ($this->placeholder !== null) {
            $data['placeholder'] = $this->placeholder;
        }

        return $data;
    }
}
