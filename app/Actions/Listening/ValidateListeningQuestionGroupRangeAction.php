<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningSection;
use App\Repositories\Listening\ListeningQuestionGroupRepository;

class ValidateListeningQuestionGroupRangeAction
{
    public function __construct(
        private readonly ListeningQuestionGroupRepository $groups,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(
        ListeningSection $section,
        int $start,
        int $end,
        ?int $ignoreGroupId = null,
    ): array {
        $errors = [];

        if ($start > $end) {
            $errors[] = 'Start question number cannot be greater than end question number.';

            return $errors;
        }

        $sectionStart = (int) $section->start_question_number;
        $sectionEnd = (int) $section->end_question_number;

        if ($start < $sectionStart || $end > $sectionEnd) {
            $errors[] = "Group range must stay inside section range Q{$sectionStart}–Q{$sectionEnd}.";
        }

        if ($this->groups->rangeOverlaps($section, $start, $end, $ignoreGroupId)) {
            $errors[] = 'Question range overlaps with another group in this section.';
        }

        $total = ($end - $start) + 1;

        if ($total < 1) {
            $errors[] = 'Group must contain at least one question.';
        }

        return $errors;
    }
}
