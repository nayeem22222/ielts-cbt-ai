<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

abstract class Rule implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    abstract public function validate(string $attribute, mixed $value, Closure $fail): void;
}
