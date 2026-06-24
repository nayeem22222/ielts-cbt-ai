<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class ReadingTestRendererService
{
    public function publishedTests(): Collection
    {
        return ReadingTest::query()
            ->published()
            ->withCount('passages')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findPublishedBySlug(string $slug): ReadingTest
    {
        return ReadingTest::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function loadForRenderer(ReadingTest $test): ReadingTest
    {
        return $test->load([
            'passages' => fn ($query) => $query->ordered(),
            'passages.groups' => fn ($query) => $query->ordered(),
            'passages.groups.questions' => fn ($query) => $query->orderBy('sort_order'),
            'passages.groups.questions.options',
            'passages.groups.groupOptions',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRendererState(ReadingTest $test): array
    {
        $test = $this->loadForRenderer($test);

        $passages = $test->passages->map(function (ReadingPassage $passage) use ($test): array {
            $groups = $passage->groups->map(fn (ReadingQuestionGroup $group): array => [
                'id' => $group->id,
                'title' => $group->title,
                'instruction' => $group->instruction,
                'type' => $group->question_type?->value,
                'question_from' => $group->start_question,
                'question_to' => $group->end_question,
                'question_range' => $group->question_range_label,
            ])->values()->all();

            $questions = $this->questionsForPassage($passage);

            return [
                'id' => $passage->id,
                'part_number' => $passage->part_number,
                'part_label' => 'Part '.($passage->part_number ?: $passage->sort_order),
                'title' => $passage->title,
                'subtitle' => $passage->subtitle,
                'instruction' => $passage->instruction,
                'question_from' => $passage->start_question,
                'question_to' => $passage->end_question,
                'question_range' => $passage->question_range_label,
                'question_count' => $questions->count(),
                'groups' => $groups,
                'questions' => $questions->map(fn (ReadingQuestion $question): array => [
                    'id' => $question->id,
                    'number' => $question->question_number,
                    'group_id' => $question->group_id,
                ])->values()->all(),
            ];
        })->values()->all();

        $allQuestions = $test->passages
            ->flatMap(fn (ReadingPassage $passage) => $this->questionsForPassage($passage))
            ->sortBy('question_number')
            ->values()
            ->map(fn (ReadingQuestion $question): array => [
                'id' => $question->id,
                'number' => $question->question_number,
                'passage_id' => $question->group?->passage_id,
                'group_id' => $question->group_id,
            ])
            ->all();

        $firstPassage = $test->passages->first();

        return [
            'testId' => $test->id,
            'testTitle' => $test->title,
            'examType' => $test->exam_type_label,
            'durationMinutes' => $test->duration_minutes,
            'passages' => $passages,
            'questions' => $allQuestions,
            'initialPassageId' => $firstPassage?->id,
            'initialQuestionNumber' => $allQuestions[0]['number'] ?? null,
        ];
    }

    /**
     * @return BaseCollection<int, ReadingQuestion>
     */
    public function questionsForPassage(ReadingPassage $passage): BaseCollection
    {
        return $passage->groups
            ->flatMap(fn (ReadingQuestionGroup $group) => $group->questions)
            ->filter(fn (ReadingQuestion $question): bool => $question->question_number > 0)
            ->sortBy('question_number')
            ->values();
    }

    /**
     * @return array<int, ReadingQuestion>
     */
    public function questionsByNumberForGroup(ReadingQuestionGroup $group): array
    {
        $map = [];

        foreach ($group->questions as $question) {
            $map[(int) $question->question_number] = $question;
        }

        return $map;
    }

    public function diagramImageUrl(ReadingQuestionGroup $group): ?string
    {
        $settings = $group->settings ?? [];

        if (empty($settings['diagram_image'])) {
            return null;
        }

        return route('reading-tests.groups.diagram-image', $group);
    }
}
