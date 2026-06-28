<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Actions\Listening\ValidateListeningQuestionNumberAction;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidListeningQuestionNumber implements ValidationRule
{
    public function __construct(
        private readonly ListeningTest $test,
        private readonly ListeningSection $section,
        private readonly ListeningQuestionGroup $group,
        private readonly ?int $ignoreQuestionId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = app(ValidateListeningQuestionNumberAction::class)->execute(
            $this->test,
            $this->section,
            $this->group,
            (int) $value,
            $this->ignoreQuestionId,
        );

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
