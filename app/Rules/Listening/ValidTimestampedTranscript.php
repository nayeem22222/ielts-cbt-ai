<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Actions\Listening\ValidateTimestampedTranscriptAction;
use App\Models\Listening\ListeningAudio;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTimestampedTranscript implements ValidationRule
{
    public function __construct(
        private readonly ?int $audioId = null,
        private readonly ?string $plainTranscript = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The timestamped transcript must be an array.');

            return;
        }

        $duration = null;

        if ($this->audioId !== null) {
            $audio = ListeningAudio::query()->find($this->audioId);
            $duration = $audio?->duration_seconds !== null ? (float) $audio->duration_seconds : null;
        }

        $errors = app(ValidateTimestampedTranscriptAction::class)->execute(
            $value,
            $duration,
            $this->plainTranscript,
        );

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
