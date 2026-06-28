<?php

declare(strict_types=1);

namespace App\Rules\Listening\QuestionTypes;

use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\CompletionBlankParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCompletionTemplate implements ValidationRule
{
    public function __construct(
        private readonly ?ListeningQuestionGroup $group = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Completion template content is required.');

            return;
        }

        if ($this->group === null) {
            $blanks = app(CompletionBlankParser::class)->extractBlankNumbers($value);

            if ($blanks === []) {
                $fail('Template must contain at least one [blank:N] marker.');
            }

            return;
        }

        $type = $this->group->question_type;

        if ($type === null) {
            return;
        }

        $service = app(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class)->serviceFor($type);
        $errors = $service->validatePayload(
            ['content' => $value, 'settings' => request()->input('settings', $this->group->settings ?? [])],
            $this->group,
            null,
            $this->group->questions,
        );

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
