<?php

declare(strict_types=1);

namespace App\Support\Listening\Builder;

final class ListeningBuilderCorrectAnswerView
{
    /**
     * @param  list<string>|null  $answer_json
     */
    public function __construct(
        public string $answer,
        public ?array $answer_json = null,
    ) {}
}
