<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningQuestionGroupRepository;
use App\Repositories\Listening\ListeningQuestionRepository;
use App\Repositories\Listening\ListeningSectionRepository;
use Illuminate\Database\Eloquent\Collection;

class ListeningQuestionBuilderService
{
    public function __construct(
        private readonly ListeningQuestionRepository $questions,
        private readonly ListeningQuestionGroupRepository $groups,
        private readonly ListeningSectionRepository $sections,
    ) {}

    /**
     * @return Collection<int, ListeningSection>
     */
    public function sectionsForBuilder(ListeningTest $test): Collection
    {
        return $this->sections->forTest($test)
            ->load([
                'audio',
                'transcript',
                'questionGroups' => fn ($query) => $query->withCount('questions')->ordered(),
            ]);
    }

    /**
     * @param  Collection<int, ListeningSection>  $sectionList
     * @return array{0: ?ListeningSection, 1: ?ListeningQuestionGroup}
     */
    public function resolveBuilderSelection(
        ListeningTest $test,
        Collection $sectionList,
        int $sectionId,
        int $groupId,
    ): array {
        if ($groupId > 0) {
            foreach ($sectionList as $section) {
                $match = $section->questionGroups->first(
                    fn (ListeningQuestionGroup $group): bool => (int) $group->getKey() === $groupId,
                );

                if ($match instanceof ListeningQuestionGroup) {
                    return [$section, $match];
                }
            }

            $selectedGroup = ListeningQuestionGroup::query()
                ->withCount('questions')
                ->whereKey($groupId)
                ->where('listening_test_id', $test->id)
                ->first();

            if ($selectedGroup instanceof ListeningQuestionGroup) {
                $selectedSection = $sectionList->firstWhere('id', (int) $selectedGroup->listening_section_id)
                    ?? $selectedGroup->section;

                return [$selectedSection, $selectedGroup];
            }
        }

        $selectedSection = null;

        if ($sectionId > 0) {
            $selectedSection = $sectionList->firstWhere('id', $sectionId)
                ?? $sectionList->firstWhere('section_number', $sectionId);
        } else {
            $selectedSection = $sectionList->first();
        }

        return [$selectedSection, null];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTestBuilderSummary(ListeningTest $test): array
    {
        $sections = $this->sections->forTest($test);
        $questionsCount = $this->questions->countForTest($test, true);
        $groupsCount = $this->groups->query()->where('listening_test_id', $test->id)->where('is_active', true)->count();
        $missing = $this->getMissingQuestionNumbersForTest($test);
        $duplicates = $this->getDuplicateQuestionNumbers($test);
        $invalid = $this->getInvalidQuestionNumbers($test);

        return [
            'questions_count' => $questionsCount,
            'expected_questions' => ListeningConstants::TOTAL_QUESTIONS,
            'groups_count' => $groupsCount,
            'sections_count' => $sections->count(),
            'missing_numbers' => $missing,
            'duplicate_numbers' => $duplicates,
            'invalid_numbers' => $invalid,
            'completion_percentage' => $this->getCompletionPercentage($test),
            'is_complete' => $questionsCount === ListeningConstants::TOTAL_QUESTIONS
                && $missing === []
                && $duplicates === []
                && $invalid === [],
            'sections' => $sections->map(fn (ListeningSection $section) => $this->getSectionBuilderSummary($section))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSectionBuilderSummary(ListeningSection $section): array
    {
        $section->loadMissing(['audio', 'transcript']);
        $groups = $this->groups->forSection($section);
        $questions = $this->questions->forSection($section)->where('is_active', true);
        $missing = $this->getMissingQuestionNumbersForSection($section);
        $duplicate = $questions->pluck('question_number')->duplicates()->values()->all();
        $invalid = array_values(array_filter(
            $questions->pluck('question_number')->map(fn ($n) => (int) $n)->all(),
            fn (int $n) => $n < (int) $section->start_question_number || $n > (int) $section->end_question_number,
        ));

        return [
            'section_id' => $section->id,
            'section_number' => $section->section_number,
            'title' => $section->title,
            'range' => [
                'start' => (int) $section->start_question_number,
                'end' => (int) $section->end_question_number,
            ],
            'groups_count' => $groups->count(),
            'questions_count' => $questions->count(),
            'expected_questions' => (int) $section->total_questions,
            'missing_numbers' => $missing,
            'duplicate_numbers' => $duplicate,
            'invalid_numbers' => $invalid,
            'has_audio' => $section->audio_id !== null,
            'has_transcript' => $section->transcript_id !== null,
            'is_complete' => $questions->count() === (int) $section->total_questions && $missing === [],
            'groups' => $groups->map(function (ListeningQuestionGroup $group): array {
                $groupQuestions = $this->questions->forGroup($group)->where('is_active', true);
                $missingGroup = [];

                for ($i = (int) $group->start_question_number; $i <= (int) $group->end_question_number; $i++) {
                    if (! $groupQuestions->contains('question_number', $i)) {
                        $missingGroup[] = $i;
                    }
                }

                return [
                    'id' => $group->id,
                    'title' => $group->title,
                    'question_type' => $group->question_type?->value,
                    'question_type_label' => $group->question_type?->label(),
                    'start' => (int) $group->start_question_number,
                    'end' => (int) $group->end_question_number,
                    'total_questions' => (int) $group->total_questions,
                    'questions_count' => $groupQuestions->count(),
                    'missing_numbers' => $missingGroup,
                    'layout_type' => $group->layout_type?->value,
                ];
            })->all(),
        ];
    }

    /**
     * @return list<int>
     */
    public function getMissingQuestionNumbersForTest(ListeningTest $test): array
    {
        return $this->questions->missingNumbersForTest($test, true);
    }

    /**
     * @return list<int>
     */
    public function getMissingQuestionNumbersForSection(ListeningSection $section): array
    {
        return $this->questions->missingNumbersForSection($section, true);
    }

    /**
     * @return list<int>
     */
    public function getDuplicateQuestionNumbers(ListeningTest $test): array
    {
        return $this->questions->duplicateNumbersForTest($test);
    }

    /**
     * @return list<int>
     */
    public function getInvalidQuestionNumbers(ListeningTest $test): array
    {
        $invalid = [];
        $questions = $this->questions->forTest($test, true);
        $sections = $this->sections->forTest($test)->keyBy('id');

        foreach ($questions as $question) {
            $number = (int) $question->question_number;
            $section = $sections->get($question->listening_section_id);

            if ($number < ListeningConstants::MIN_QUESTION_NUMBER || $number > ListeningConstants::MAX_QUESTION_NUMBER) {
                $invalid[] = $number;

                continue;
            }

            if ($section !== null && ($number < (int) $section->start_question_number || $number > (int) $section->end_question_number)) {
                $invalid[] = $number;
            }
        }

        return array_values(array_unique($invalid));
    }

    public function getCompletionPercentage(ListeningTest $test): float
    {
        $count = $this->questions->countForTest($test, true);

        return round(($count / ListeningConstants::TOTAL_QUESTIONS) * 100, 2);
    }

    /**
     * @return array<string, int>
     */
    public function getQuestionTypeDistribution(ListeningTest $test): array
    {
        $distribution = [];

        foreach ($this->questions->forTest($test, true) as $question) {
            $key = $question->question_type?->value ?? 'unknown';
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }

        return $distribution;
    }
}
