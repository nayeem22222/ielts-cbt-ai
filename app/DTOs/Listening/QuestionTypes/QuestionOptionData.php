<?php

declare(strict_types=1);

namespace App\DTOs\Listening\QuestionTypes;

readonly class QuestionOptionData
{
    public function __construct(
        public string $key,
        public string $text,
        public bool $isCorrect = false,
    ) {}

    /**
     * @return array{key: string, text: string, is_correct: bool}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'text' => $this->text,
            'is_correct' => $this->isCorrect,
        ];
    }
}
