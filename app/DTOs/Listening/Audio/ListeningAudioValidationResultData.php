<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio;

use App\Enums\Listening\ListeningAudioValidationStatus;

final readonly class ListeningAudioValidationResultData
{
    /**
     * @param  list<array{code: string, message: string}>  $errors
     */
    public function __construct(
        public ListeningAudioValidationStatus $status,
        public array $errors = [],
    ) {}

    public function isValid(): bool
    {
        return $this->status === ListeningAudioValidationStatus::Valid;
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
