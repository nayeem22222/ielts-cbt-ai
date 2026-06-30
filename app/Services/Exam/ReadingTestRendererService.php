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
    public function __construct(
        private readonly ReadingTestPublicCacheService $cache,
        private readonly ReadingMultipleChoiceMultipleCountingService $mcqMultipleCounting,
    ) {
    }

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

    public function cachedForRenderer(ReadingTest $test): ReadingTest
    {
        return $this->cache->remember(
            $test,
            fn (ReadingTest $fresh): ReadingTest => $this->loadForRenderer($fresh),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRendererState(ReadingTest $test): array
    {
        if (! $test->relationLoaded('passages')) {
            $test = $this->cachedForRenderer($test);
        }

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

            $questions = $this->navigatorQuestionsForPassage($passage);

            return [
                'id' => $passage->id,
                'part_number' => $passage->part_number,
                'part_label' => 'Part '.($passage->part_number ?: $passage->sort_order),
                'title' => $passage->title,
                'subtitle' => $passage->subtitle,
                'instruction' => $passage->instruction,
                'question_from' => $passage->start_question,
                'question_to' => $passage->end_question,
                'question_range' => $this->questionRangeLabelFromNumbers($questions, $passage),
                'question_range_configured' => $passage->question_range_label,
                'question_count' => $questions->count(),
                'groups' => $groups,
                'questions' => $questions->map(fn (array $question): array => [
                    'id' => $question['id'],
                    'number' => $question['number'],
                    'group_id' => $question['group_id'],
                ])->values()->all(),
            ];
        })->values()->all();

        $allQuestions = $test->passages
            ->flatMap(fn (ReadingPassage $passage) => $this->navigatorQuestionsForPassage($passage))
            ->sortBy('number')
            ->values()
            ->map(fn (array $question): array => [
                'id' => $question['id'],
                'number' => $question['number'],
                'passage_id' => $question['passage_id'],
                'group_id' => $question['group_id'],
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

    /**
     * @return BaseCollection<int, array{id: int|null, number: int, group_id: int, passage_id: int}>
     */
    public function navigatorQuestionsForPassage(ReadingPassage $passage): BaseCollection
    {
        $items = collect();
        $processedMcqGroups = [];
        $seenNumbers = [];
        $reservedNumbers = $this->mcqMultipleCounting->reservedQuestionNumbersForPassage($passage);

        foreach ($passage->groups as $group) {
            if ($this->mcqMultipleCounting->isMcqMultipleGroup($group)) {
                $groupId = (int) $group->id;

                if (isset($processedMcqGroups[$groupId])) {
                    continue;
                }

                $processedMcqGroups[$groupId] = true;
                $primary = $this->mcqMultipleCounting->resolvePrimaryQuestion($group);

                foreach ($this->mcqMultipleCounting->groupQuestionNumbers($group) as $number) {
                    $seenNumbers[$number] = true;
                    $items->push([
                        'id' => $primary?->id,
                        'number' => $number,
                        'group_id' => $groupId,
                        'passage_id' => $passage->id,
                    ]);
                }

                continue;
            }

            foreach ($group->questions as $question) {
                $number = (int) $question->question_number;

                if ($number <= 0 || isset($reservedNumbers[$number]) || isset($seenNumbers[$number])) {
                    continue;
                }

                $seenNumbers[$number] = true;
                $items->push([
                    'id' => $question->id,
                    'number' => $number,
                    'group_id' => (int) $group->id,
                    'passage_id' => $passage->id,
                ]);
            }
        }

        return $items->sortBy('number')->values();
    }

    /**
     * @param  BaseCollection<int, array{number: int}>  $questions
     */
    public function questionRangeLabelFromNumbers(BaseCollection $questions, ReadingPassage $passage): string
    {
        $numbers = $questions
            ->pluck('number')
            ->filter(fn (int $number): bool => $number > 0)
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return $passage->question_range_label ?? '—';
        }

        $min = (int) $numbers->first();
        $max = (int) $numbers->last();

        return $min === $max ? (string) $min : "{$min}-{$max}";
    }

    /**
     * @param  BaseCollection<int, ReadingQuestion>  $questions
     */
    public function questionRangeLabel(BaseCollection $questions, ReadingPassage $passage): string
    {
        $numbers = $questions
            ->pluck('question_number')
            ->filter(fn (int $number): bool => $number > 0)
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return $passage->question_range_label ?? '—';
        }

        $min = (int) $numbers->first();
        $max = (int) $numbers->last();

        return $min === $max ? (string) $min : "{$min}-{$max}";
    }
}
