<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createReadingTestForGroupBuilder(): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'group-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    return ReadingTest::query()->create([
        'title' => 'Question Group Builder Test',
        'slug' => 'group-builder-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
}

function latestPassageForGroups(ReadingTest $test): ReadingPassage
{
    return ReadingPassage::query()
        ->where('reading_test_id', $test->id)
        ->orderByDesc('id')
        ->firstOrFail();
}

function passagePayloadForGroups(array $overrides = []): array
{
    return array_merge([
        'title' => 'Sample Passage',
        'subtitle' => null,
        'instruction' => 'Read the passage and answer the questions.',
        'start_question' => 1,
        'end_question' => 13,
        'content_html' => '<p>Passage content.</p>',
        'status' => PassageStatus::Draft->value,
        'auto_paragraph_labels' => true,
    ], $overrides);
}

function groupPayload(ReadingQuestionGroup $group, array $overrides = []): array
{
    return array_merge([
        'title' => $group->title,
        'instruction' => $group->instruction,
        'question_type' => $group->question_type?->value ?? OfficialReadingQuestionType::MatchingInformation->value,
        'start_question' => $group->start_question,
        'end_question' => $group->end_question,
        'sort_order' => $group->sort_order,
        'status' => $group->status?->value ?? PassageStatus::Draft->value,
    ], $overrides);
}

function bootstrapThreePassageReadingTest(): array
{
    $test = createReadingTestForGroupBuilder();

    foreach ([
        ['title' => 'Passage One', 'start_question' => 1, 'end_question' => 13],
        ['title' => 'Passage Two', 'start_question' => 14, 'end_question' => 26],
        ['title' => 'Passage Three', 'start_question' => 27, 'end_question' => 40],
    ] as $data) {
        test()->post(route('admin.reading-tests.passages.store', $test));
        $passage = latestPassageForGroups($test);

        test()->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayloadForGroups($data))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    $passages = ReadingPassage::query()
        ->where('reading_test_id', $test->id)
        ->orderBy('part_number')
        ->get();

    return [$test, $passages];
}

it('renders builder with passages and question groups sidebar', function (): void {
    $test = createReadingTestForGroupBuilder();

    $this->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Passages & Question Groups')
        ->assertSee('Add Passage')
        ->assertSee('Passage Editor');
});

it('creates blank question group and opens group editor', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages->first();

    $response = $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->first();

    expect($group)->not->toBeNull();
    expect($group->title)->toBe('Questions 1–4');
    expect($group->start_question)->toBe(1);
    expect($group->end_question)->toBe(4);

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('Question Group Editor')
        ->assertSee('Save Question Group')
        ->assertDontSee('id="content_html"', false);
});

it('opens group editor for a second passage question group', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[1];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->firstOrFail();

    $this->get(route('admin.reading-tests.builder', [
        'readingTest' => $test,
        'passage' => $passage->id,
        'question_group' => $group->id,
    ]))
        ->assertOk()
        ->assertSee('Question Group Editor')
        ->assertSee('Save Question Group')
        ->assertSee('Questions 14–17')
        ->assertDontSee('id="content_html"', false);
});

it('opens group editor when question_group query key is malformed', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[1];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));

    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->firstOrFail();

    $this->get('/admin/reading-tests/'.$test->id.'/builder?passage='.$passage->id.'&ampcquestion_group='.$group->id)
        ->assertOk()
        ->assertSee('Question Group Editor')
        ->assertDontSee('id="content_html"', false);
});

it('builds official academic question group layout across three passages', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();

    $layout = [
        0 => [[1, 4], [5, 8], [9, 13]],
        1 => [[14, 20], [21, 26]],
        2 => [[27, 29], [30, 35], [36, 40]],
    ];

    foreach ($layout as $passageIndex => $ranges) {
        $passage = $passages[$passageIndex];

        foreach ($ranges as [$start, $end]) {
            $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]))
                ->assertRedirect();

            $group = ReadingQuestionGroup::query()
                ->where('passage_id', $passage->id)
                ->orderByDesc('id')
                ->firstOrFail();

            $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
                'title' => "Questions {$start}–{$end}",
                'start_question' => $start,
                'end_question' => $end,
                'question_type' => OfficialReadingQuestionType::SummaryCompletion->value,
            ]))->assertRedirect()->assertSessionHasNoErrors();
        }
    }

    expect(ReadingQuestionGroup::query()->count())->toBe(8);

    $this->get(route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passages[0]->id]))
        ->assertOk()
        ->assertSee('Questions 1–4')
        ->assertSee('Questions 9–13');
});

it('persists question groups after refresh', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];

    foreach ([[1, 4], [5, 8], [9, 13]] as [$start, $end]) {
        $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
        $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderByDesc('id')->firstOrFail();

        $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
            'title' => "Questions {$start}–{$end}",
            'start_question' => $start,
            'end_question' => $end,
        ]));
    }

    $this->get(route('admin.reading-tests.builder', [
        'readingTest' => $test,
        'passage' => $passage->id,
        'question_group' => ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderBy('start_question')->first()->id,
    ]))
        ->assertOk()
        ->assertSee('Questions 1–4')
        ->assertSee('Questions 5–8')
        ->assertSee('Questions 9–13')
        ->assertSee('Question Group Editor');
});

it('rejects overlapping question ranges within a passage', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $first = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderBy('id')->firstOrFail();

    $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $first]), groupPayload($first, [
        'title' => 'Questions 1–4',
        'start_question' => 1,
        'end_question' => 4,
    ]))->assertRedirect();

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $second = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderByDesc('id')->firstOrFail();

    $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $second]), groupPayload($second, [
        'title' => 'Questions 3–8',
        'start_question' => 3,
        'end_question' => 8,
    ]))->assertSessionHasErrors('start_question');
});

it('rejects question ranges outside passage bounds', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->firstOrFail();

    $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
        'title' => 'Questions 12–20',
        'start_question' => 12,
        'end_question' => 20,
    ]))->assertSessionHasErrors('end_question');
});

it('duplicates question group without copying questions', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->firstOrFail();

    $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
        'title' => 'Questions 1–4',
        'instruction' => 'Custom instruction',
        'question_type' => OfficialReadingQuestionType::MatchingHeadings->value,
        'start_question' => 1,
        'end_question' => 4,
    ]));

    $this->post(route('admin.reading-tests.passages.groups.duplicate', [$test, $passage, $group]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $groups = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderBy('id')->get();

    expect($groups)->toHaveCount(2);
    expect($groups[1]->title)->toBe('Questions 1–4');
    expect($groups[1]->instruction)->toBe('Custom instruction');
    expect($groups[1]->question_type)->toBe(OfficialReadingQuestionType::MatchingHeadings);
    expect($groups[1]->questions()->count())->toBe(0);
    expect($groups[1]->start_question)->toBe(5);
    expect($groups[1]->end_question)->toBe(8);
});

it('deletes question group within transaction', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];

    $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->firstOrFail();

    $this->delete(route('admin.reading-tests.passages.groups.destroy', [$test, $passage, $group]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(ReadingQuestionGroup::query()->where('passage_id', $passage->id)->count())->toBe(0);
});

it('reorders question groups via drag endpoint', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];
    $groupIds = [];

    foreach ([[1, 4], [5, 8], [9, 13]] as [$start, $end]) {
        $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
        $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderByDesc('id')->firstOrFail();
        $groupIds[] = $group->id;

        $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
            'title' => "Questions {$start}–{$end}",
            'start_question' => $start,
            'end_question' => $end,
        ]));
    }

    $reordered = array_reverse($groupIds);

    $this->post(route('admin.reading-tests.passages.groups.reorder', [$test, $passage]), [
        'group_ids' => $reordered,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $titles = ReadingQuestionGroup::query()
        ->where('passage_id', $passage->id)
        ->orderBy('sort_order')
        ->pluck('title')
        ->all();

    expect($titles)->toBe([
        'Questions 9–13',
        'Questions 5–8',
        'Questions 1–4',
    ]);
});

it('moves question groups up and down', function (): void {
    [$test, $passages] = bootstrapThreePassageReadingTest();
    $passage = $passages[0];
    $groups = [];

    foreach ([[1, 4], [5, 8], [9, 13]] as [$start, $end]) {
        $this->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
        $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->orderByDesc('id')->firstOrFail();
        $groups[] = $group;

        $this->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), groupPayload($group, [
            'title' => "Questions {$start}–{$end}",
            'start_question' => $start,
            'end_question' => $end,
        ]));
    }

    $this->post(route('admin.reading-tests.passages.groups.move-down', [$test, $passage, $groups[0]]))
        ->assertRedirect();

    $titles = ReadingQuestionGroup::query()
        ->where('passage_id', $passage->id)
        ->orderBy('sort_order')
        ->pluck('title')
        ->all();

    expect($titles)->toBe([
        'Questions 5–8',
        'Questions 1–4',
        'Questions 9–13',
    ]);
});
