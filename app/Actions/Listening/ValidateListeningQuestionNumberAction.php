<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningQuestionRepository;

class ValidateListeningQuestionNumberAction
{
    public function __construct(
        private readonly ListeningQuestionRepository $questions,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        int $questionNumber,
        ?int $ignoreQuestionId = null,
    ): array {
        $errors = [];

        if ($questionNumber < ListeningConstants::MIN_QUESTION_NUMBER || $questionNumber > ListeningConstants::MAX_QUESTION_NUMBER) {
            $errors[] = 'Question number must be between '.ListeningConstants::MIN_QUESTION_NUMBER.' and '.ListeningConstants::MAX_QUESTION_NUMBER.'.';
        }

        if ($questionNumber < (int) $section->start_question_number || $questionNumber > (int) $section->end_question_number) {
            $errors[] = 'Question number is outside the section range.';
        }

        if ($questionNumber < (int) $group->start_question_number || $questionNumber > (int) $group->end_question_number) {
            $errors[] = 'Question number is outside the group range.';
        }

        if ($this->questions->questionNumberExists($test, $questionNumber, $ignoreQuestionId)) {
            $errors[] = 'Question number already exists for this listening test.';
        }

        return $errors;
    }
}
