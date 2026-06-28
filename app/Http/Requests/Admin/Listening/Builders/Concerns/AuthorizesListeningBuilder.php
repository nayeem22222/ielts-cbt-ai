<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening\Builders\Concerns;

use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningTest;

trait AuthorizesListeningBuilder
{
    protected function listeningGroupFromRoute(): ?ListeningQuestionGroup
    {
        $group = $this->route('group');

        if ($group instanceof ListeningQuestionGroup) {
            return $group;
        }

        $question = $this->route('question');

        if ($question instanceof ListeningQuestion) {
            return $question->group;
        }

        return null;
    }

    protected function listeningTestForAuthorization(): ?ListeningTest
    {
        $group = $this->listeningGroupFromRoute();

        return $group?->section?->test;
    }

    public function authorize(): bool
    {
        $test = $this->listeningTestForAuthorization();

        return $test instanceof ListeningTest
            && ($this->user()?->can('update', $test) ?? false);
    }
}
