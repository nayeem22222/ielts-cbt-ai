<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class CourseSlugRequest extends FormRequest
{
    protected function prepareSlug(string $sourceField = 'title'): void
    {
        $slugField = 'slug';
        $source = $this->has($sourceField) ? $sourceField : 'name';

        if (! $this->filled($slugField) && $this->filled($source)) {
            $this->merge([
                $slugField => Str::slug($this->string($source)->toString()),
            ]);
        }
    }
}
