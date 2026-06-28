<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLabellingPoints implements ValidationRule
{
    public function __construct(
        private readonly ListeningQuestionType $type = ListeningQuestionType::MapLabelling,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Labelling options must be an object.');

            return;
        }

        $service = app(ListeningQuestionTypeRegistry::class)->serviceFor($this->type);
        $errors = $service->validatePayload([
            'options' => $value,
            'image_path' => request()->input('image_path'),
        ]);

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
