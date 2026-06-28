<?php

declare(strict_types=1);

namespace App\Support\Listening;

use App\Models\Listening\ListeningQuestionGroup;

final class ListeningQuestionBuilderRoutes
{
    public static function manageQuestionsUrl(ListeningQuestionGroup $group): string
    {
        $type = $group->question_type;

        if ($type === null) {
            return route('admin.listening.tests.builder.index', [
                'listeningTest' => $group->listening_test_id,
                'section' => $group->listening_section_id,
                'question_group' => $group->id,
            ]);
        }

        return route($type->questionBuilderRouteName(), $group);
    }

    public static function backToGroupUrl(ListeningQuestionGroup $group): string
    {
        return route('admin.listening.tests.builder.index', [
            'listeningTest' => $group->listening_test_id,
            'section' => $group->listening_section_id,
            'question_group' => $group->id,
        ]);
    }
}
