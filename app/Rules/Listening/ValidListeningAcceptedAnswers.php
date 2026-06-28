<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidListeningAcceptedAnswers implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        if (! is_array($value)) {
            $fail('Accepted answers must be a valid array.');

            return;
        }

        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                $fail('Accepted answer entry '.($index + 1).' is invalid.');

                return;
            }

            if (! isset($item['value']) || trim((string) $item['value']) === '') {
                $fail('Accepted answer entry '.($index + 1).' must include a value.');
            }
        }
    }
}
