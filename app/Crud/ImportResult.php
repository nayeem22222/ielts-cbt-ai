<?php

declare(strict_types=1);

namespace App\Crud;

final class ImportResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly int $imported = 0,
        public readonly int $skipped = 0,
        public readonly array $errors = [],
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
