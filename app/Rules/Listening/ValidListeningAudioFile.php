<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Services\Listening\Audio\ListeningAudioValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidListeningAudioFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('A valid audio file is required.');

            return;
        }

        $result = app(ListeningAudioValidationService::class)->validateFile($value);

        if (! $result->isValid()) {
            $fail($result->errors()[0]['message'] ?? 'Invalid audio file.');
        }
    }
}
