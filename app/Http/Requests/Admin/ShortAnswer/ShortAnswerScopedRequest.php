<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ShortAnswer;

use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;

abstract class ShortAnswerScopedRequest extends FormRequest
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

    protected function questionFromRoute(): ?ReadingQuestion
    {
        $question = $this->route('question');

        if ($question instanceof ReadingQuestion) {
            return $question;
        }

        if (is_numeric($question)) {
            return ReadingQuestion::query()->find((int) $question);
        }

        return null;
    }
}
