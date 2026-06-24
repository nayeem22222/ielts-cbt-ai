<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;

abstract class ObjectiveQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->objectiveGroup();

        if (! $group instanceof ReadingQuestionGroup) {
            return false;
        }

        $test = $group->passage?->test;

        return $test instanceof ReadingTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    protected function objectiveGroup(): ?ReadingQuestionGroup
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
}
