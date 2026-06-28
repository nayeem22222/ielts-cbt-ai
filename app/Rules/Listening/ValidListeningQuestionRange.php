<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Actions\Listening\ValidateListeningQuestionGroupRangeAction;
use App\Models\Listening\ListeningSection;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidListeningQuestionRange implements ValidationRule
{
    public function __construct(
        private readonly ListeningSection $section,
        private readonly int $start,
        private readonly ?int $ignoreGroupId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = app(ValidateListeningQuestionGroupRangeAction::class)->execute(
            $this->section,
            $this->start,
            (int) $value,
            $this->ignoreGroupId,
        );

        if ($errors !== []) {
            $fail($errors[0]);
        }
    }
}
