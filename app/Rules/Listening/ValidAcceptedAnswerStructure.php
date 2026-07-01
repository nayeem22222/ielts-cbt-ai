<?php

declare(strict_types=1);

namespace App\Rules\Listening;

use App\Services\Listening\Evaluation\Normalization\ListeningRegexAnswerMatcher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAcceptedAnswerStructure implements ValidationRule
{
    /** @var list<string> */
    private array $types = [
        'text',
        'number',
        'date',
        'time',
        'letter',
        'regex',
        'matching',
        'map_label',
        'diagram_label',
    ];

    public function __construct(
        private readonly ?ListeningRegexAnswerMatcher $regex = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            $fail('Accepted answers must be a non-empty array.');

            return;
        }

        $regex = $this->regex ?? app(ListeningRegexAnswerMatcher::class);

        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                $fail("Accepted answer {$index} must be an object.");

                return;
            }

            $answerValue = trim((string) ($item['value'] ?? ''));
            $type = (string) ($item['type'] ?? '');

            if ($answerValue === '') {
                $fail("Accepted answer {$index} requires a value.");

                return;
            }

            if (! in_array($type, $this->types, true)) {
                $fail("Accepted answer {$index} has an unsupported type.");

                return;
            }

            if ($type === 'regex' && ! $regex->validateRegex($answerValue)) {
                $fail("Accepted answer {$index} has an invalid or unsafe regex.");

                return;
            }
        }
    }
}
