<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMcqOptions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $fail('MCQ options must be a list.');

            return;
        }

        $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::MCQ);
        $errors = $service->validatePayload(['options' => $value]);

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
