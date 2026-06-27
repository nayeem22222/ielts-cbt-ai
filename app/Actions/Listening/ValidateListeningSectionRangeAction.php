<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningSection;
use App\Support\Listening\ListeningSectionMap;

class ValidateListeningSectionRangeAction
{
    /**
     * @return list<string>
     */
    public function validateSectionNumber(int $sectionNumber): array
    {
        if (! ListeningSectionMap::isValidSectionNumber($sectionNumber)) {
            return ['Section number must be between 1 and 4.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    public function validateSection(ListeningSection $section): array
    {
        $errors = $this->validateSectionNumber((int) $section->section_number);

        if ($errors !== []) {
            return $errors;
        }

        $expected = ListeningSectionMap::forSectionNumber((int) $section->section_number);

        if ((int) $section->start_question_number !== $expected['start']) {
            $errors[] = "Section {$section->section_number} must start at question {$expected['start']}.";
        }

        if ((int) $section->end_question_number !== $expected['end']) {
            $errors[] = "Section {$section->section_number} must end at question {$expected['end']}.";
        }

        if ((int) $section->total_questions !== $expected['total']) {
            $errors[] = "Section {$section->section_number} must contain exactly {$expected['total']} questions.";
        }

        return $errors;
    }
}
