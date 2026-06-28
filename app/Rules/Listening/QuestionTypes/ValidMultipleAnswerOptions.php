<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMultipleAnswerOptions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $options = is_array($value) ? $value : [];
        $settings = request()->input('settings', []);
        $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::MultipleAnswer);
        $errors = $service->validatePayload(['options' => $options, 'settings' => $settings]);

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
