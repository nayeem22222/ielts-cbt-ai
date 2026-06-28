<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening\Concerns;

trait DecodesListeningJsonAttributes
{
    /**
     * @param  list<string>  $fields
     */
    protected function decodeJsonAttributes(array $fields): void
    {
        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }

            if ($value === '' || $value === null) {
                $this->merge([$field => null]);
            }
        }
    }

    protected function normalizeNullableIntegers(array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => null]);
            }
        }
    }
}
