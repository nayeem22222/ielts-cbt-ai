<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningTest;
use Illuminate\Support\Facades\DB;

class PublishListeningTestAction
{
    /**
     * @return array{success: bool, errors: list<string>}
     */
    public function validate(ListeningTest $test): array
    {
        $test->loadMissing([
            'setting',
            'sections' => fn ($query) => $query->where('is_active', true),
            'questions' => fn ($query) => $query->where('is_active', true),
            'questionGroups' => fn ($query) => $query->where('is_active', true),
        ]);

        $errors = [];

        if ($test->status === ListeningTestStatus::Archived) {
            $errors[] = 'Archived listening tests cannot be published.';
        }

        $activeSections = $test->sections;

        if ($activeSections->count() !== ListeningConstants::TOTAL_SECTIONS) {
            $errors[] = 'Listening test must have exactly '.ListeningConstants::TOTAL_SECTIONS.' active sections.';
        }

        $activeQuestions = $test->questions;

        if ($activeQuestions->count() !== ListeningConstants::TOTAL_QUESTIONS) {
            $errors[] = 'Listening test must have exactly '.ListeningConstants::TOTAL_QUESTIONS.' active questions.';
        }

        foreach ($activeSections as $section) {
            if ($section->audio_id === null) {
                $errors[] = "Section {$section->section_number} is missing audio.";
            }

            $expectedRange = ListeningConstants::SECTION_QUESTION_RANGES[$section->section_number] ?? null;

            if ($expectedRange === null) {
                $errors[] = "Section {$section->section_number} has an invalid section number.";

                continue;
            }

            if (
                (int) $section->start_question_number !== $expectedRange['start']
                || (int) $section->end_question_number !== $expectedRange['end']
            ) {
                $errors[] = "Section {$section->section_number} must cover questions {$expectedRange['start']}–{$expectedRange['end']}.";
            }
        }

        $questionNumbers = $activeQuestions->pluck('question_number')->map(fn ($number) => (int) $number);

        if ($questionNumbers->duplicates()->isNotEmpty()) {
            $errors[] = 'Duplicate question numbers exist in this listening test.';
        }

        for ($number = ListeningConstants::MIN_QUESTION_NUMBER; $number <= ListeningConstants::MAX_QUESTION_NUMBER; $number++) {
            if (! $questionNumbers->contains($number)) {
                $errors[] = "Question number {$number} is missing.";
            }
        }

        foreach ($activeQuestions as $question) {
            if ($question->correct_answer === null || $question->correct_answer === [] || $question->correct_answer === '') {
                $errors[] = "Question {$question->question_number} is missing a correct answer.";
            }
        }

        $groups = $test->questionGroups->sortBy('start_question_number')->values();

        /** @var ListeningQuestionGroup|null $previous */
        $previous = null;

        foreach ($groups as $group) {
            if ((int) $group->start_question_number > (int) $group->end_question_number) {
                $errors[] = "Question group \"{$group->title}\" has an invalid question range.";
            }

            if ($previous !== null && (int) $group->start_question_number <= (int) $previous->end_question_number) {
                $errors[] = 'Overlapping question group ranges detected.';
            }

            $previous = $group;
        }

        if ($test->setting === null) {
            $errors[] = 'Listening test settings are missing.';
        }

        return [
            'success' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{success: bool, errors: list<string>, test?: ListeningTest}
     */
    public function execute(ListeningTest $test): array
    {
        $validation = $this->validate($test);

        if (! $validation['success']) {
            return $validation;
        }

        $published = DB::transaction(function () use ($test): ListeningTest {
            $test->forceFill([
                'status' => ListeningTestStatus::Published,
                'is_active' => true,
                'published_at' => $test->published_at ?? now(),
                'updated_by' => auth()->id(),
            ])->save();

            return $test->refresh();
        });

        return [
            'success' => true,
            'errors' => [],
            'test' => $published,
        ];
    }
}
