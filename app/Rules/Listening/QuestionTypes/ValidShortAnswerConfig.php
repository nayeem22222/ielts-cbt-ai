<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidShortAnswerConfig implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::ShortAnswer);
        $errors = $service->validatePayload([
            'question_text' => request()->input('question_text'),
            'word_limit' => request()->input('word_limit'),
            'correct_answer' => request()->input('correct_answer'),
        ], null, new \App\Models\Listening\ListeningQuestion);

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
