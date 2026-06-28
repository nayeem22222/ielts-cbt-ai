<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes\Concerns;

use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

trait HandlesCompletionQuestionType
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function normalizeCompletionSettings(array $settings, string $templateType, int $defaultWordLimit = 2): array
    {
        return array_merge([
            'template_type' => $templateType,
            'word_limit' => $defaultWordLimit,
        ], $settings);
    }

    /**
     * @return list<string>
     */
    protected function validateCompletionContent(?string $content, ListeningQuestionGroup $group): array
    {
        if ($content === null || trim($content) === '') {
            return ['Content template is required.'];
        }

        $blanks = $this->blankParser->extractBlankNumbers($content);

        return $this->validateTemplateBlanks(
            $blanks,
            (int) $group->start_question_number,
            (int) $group->end_question_number,
        );
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningQuestion>  $questions
     * @return list<string>
     */
    protected function validateCompletionBlanksMatchQuestions(
        string $content,
        Collection $questions,
        ListeningQuestionGroup $group,
    ): array {
        return $this->blankParser->validateBlanksAgainstQuestions(
            $content,
            $questions,
            (int) $group->start_question_number,
            (int) $group->end_question_number,
        );
    }
}
