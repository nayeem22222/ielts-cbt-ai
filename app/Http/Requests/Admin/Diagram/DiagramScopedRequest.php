<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Diagram;

use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;

abstract class DiagramScopedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->resolveGroup();

        if (! $group instanceof ReadingQuestionGroup) {
            return false;
        }

        $test = $group->passage?->test;

        return $test instanceof ReadingTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    abstract protected function resolveGroup(): ?ReadingQuestionGroup;

    protected function groupFromRoute(): ?ReadingQuestionGroup
    {
        $group = $this->route('group');

        if ($group instanceof ReadingQuestionGroup) {
            return $group;
        }

        if (is_numeric($group)) {
            return ReadingQuestionGroup::query()->find((int) $group);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function imageFileRules(): array
    {
        $maxMb = max(1, (int) setting('storage', 'max_upload_mb', 25));

        return [
            'required',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'extensions:jpg,jpeg,png,webp',
            'max:'.($maxMb * 1024),
        ];
    }
}
