<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Services\Listening\Evaluation\Normalization\ListeningRegexAnswerMatcher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRegexAcceptedAnswer implements ValidationRule
{
    public function __construct(
        private readonly ?ListeningRegexAnswerMatcher $matcher = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $matcher = $this->matcher ?? app(ListeningRegexAnswerMatcher::class);
        $pattern = (string) $value;

        if (! (bool) config('listening.normalization.regex_answers.enabled', true)) {
            $fail('Regex accepted answers are disabled.');

            return;
        }

        if (! $matcher->validateRegex($pattern)) {
            $fail('The accepted answer regex is invalid or unsafe.');
        }
    }
}
