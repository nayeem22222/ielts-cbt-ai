<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation\Normalization;

final readonly class WordLimitResultData
{
    /**
     * @param  list<string>  $tokens
     */
    public function __construct(
        public bool $exceeded,
        public int $wordCount,
        public ?int $limit,
        public array $tokens,
    ) {}
}
