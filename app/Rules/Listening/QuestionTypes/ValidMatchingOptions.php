<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMatchingOptions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Matching options must be an object with choices.');

            return;
        }

        $group = request()->route('group');

        if (! $group instanceof ListeningQuestionGroup) {
            $group = null;
        }

        $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::Matching);
        $errors = $service->validatePayload(['options' => $value], $group);

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
