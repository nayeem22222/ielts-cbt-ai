<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingAnswer;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Support\Reading\ReadingGroupInteraction;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function dragDropStudent(): \App\Models\User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-dnd-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-dnd-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createDragDropReadingTest(array $overrides = []): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $slug = $overrides['slug'] ?? 'reading-dnd-'.uniqid();

    $test = ReadingTest::query()->create([
        'title' => $overrides['title'] ?? 'Drag Drop Test',
        'slug' => $slug,
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 1,
        'title' => 'Passage',
        'start_question' => 1,
        'end_question' => 4,
        'content_html' => $overrides['content_html'] ?? '<p>Paragraph A body</p><p>Paragraph B body</p>',
        'status' => PassageStatus::Published,
        'settings' => ['auto_paragraph_labels' => true],
        'sort_order' => 1,
    ]);

    $group = ReadingQuestionGroup::query()->create([
        'passage_id' => $passage->id,
        'title' => 'Questions 1–2',
        'question_type' => $overrides['question_type'] ?? OfficialReadingQuestionType::MatchingFeatures,
        'start_question' => 1,
        'end_question' => 2,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
        'settings' => $overrides['group_settings'] ?? [
            'interaction_mode' => ReadingGroupInteraction::MODE_DRAG_DROP,
            'allow_reuse' => false,
        ],
    ]);

    foreach ($overrides['options'] ?? [
        ['key' => 'A', 'label' => 'Option A'],
        ['key' => 'B', 'label' => 'Option B'],
    ] as $index => $option) {
        $group->groupOptions()->create([
            'option_key' => $option['key'],
            'option_label' => $option['label'] ?? '',
            'sort_order' => $index + 1,
        ]);
    }

    foreach ($overrides['questions'] ?? [
        ['number' => 1, 'prompt' => 'Statement one'],
        ['number' => 2, 'prompt' => 'Statement two'],
    ] as $index => $questionData) {
        $group->questions()->create([
            'question_number' => $questionData['number'],
            'prompt' => $questionData['prompt'],
            'paragraph_reference' => $questionData['paragraph_reference'] ?? null,
            'marks' => 1,
            'sort_order' => $index + 1,
        ]);
    }

    if (isset($overrides['template_html'])) {
        $group->forceFill([
            'settings' => array_merge($group->settings ?? [], [
                'template_html' => $overrides['template_html'],
                'answer_rule' => 'one_word_only',
            ]),
        ])->save();
    }

    return $test->fresh();
}

it('renders matching features drag and drop ui when interaction mode is drag_drop', function (): void {
    $student = dragDropStudent();
    $test = createDragDropReadingTest(['slug' => 'features-dnd']);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-dnd-group', false)
        ->assertSee('reading-dnd-token', false)
        ->assertSee('reading-dnd-dropzone', false)
        ->assertDontSee('reading-test-select', false);
});

it('renders matching headings drag and drop ui with passage mapping config', function (): void {
    $student = dragDropStudent();
    $test = createDragDropReadingTest([
        'slug' => 'headings-dnd',
        'question_type' => OfficialReadingQuestionType::MatchingHeadings,
        'options' => [
            ['key' => 'i', 'label' => 'Heading one'],
            ['key' => 'ii', 'label' => 'Heading two'],
        ],
        'questions' => [
            ['number' => 1, 'prompt' => 'Paragraph A', 'paragraph_reference' => 'A'],
            ['number' => 2, 'prompt' => 'Paragraph B', 'paragraph_reference' => 'B'],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-dnd-headings-config', false)
        ->assertSee('reading-dnd-token', false)
        ->assertSee('data-paragraph="A"', false)
        ->assertDontSee('reading-test-select', false);
});

it('keeps select ui for matching headings when interaction mode is select', function (): void {
    $student = dragDropStudent();
    $test = createDragDropReadingTest([
        'slug' => 'headings-select',
        'question_type' => OfficialReadingQuestionType::MatchingHeadings,
        'group_settings' => ['interaction_mode' => ReadingGroupInteraction::MODE_SELECT],
        'options' => [
            ['key' => 'i', 'label' => 'Heading one'],
        ],
        'questions' => [
            ['number' => 1, 'prompt' => 'Paragraph A', 'paragraph_reference' => 'A'],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-test-select', false)
        ->assertDontSee('reading-dnd-token', false);
});

it('saves drag drop matching answers using option keys via autosave endpoint', function (): void {
    $student = dragDropStudent();
    $test = createDragDropReadingTest(['slug' => 'features-dnd-save']);
    $group = $test->passages->first()->groups->first();
    $question = $group->questions->first();

    $attempt = ReadingAttempt::query()->create([
        'user_id' => $student->id,
        'reading_test_id' => $test->id,
        'status' => \App\Enums\Exam\TestAttemptStatus::InProgress,
        'started_at' => now(),
        'remaining_seconds' => 3600,
    ]);

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'question_type' => $group->question_type->value,
        'passage_id' => $test->passages->first()->id,
        'group_id' => $group->id,
        'answer' => 'A',
        'answer_json' => null,
    ])->assertOk();

    expect(ReadingAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->value('answer'))
        ->toBe('A');
});

it('renders summary completion drag drop blanks when options exist', function (): void {
    $student = dragDropStudent();
    $test = createDragDropReadingTest([
        'slug' => 'summary-dnd',
        'question_type' => OfficialReadingQuestionType::SummaryCompletion,
        'group_settings' => [
            'interaction_mode' => ReadingGroupInteraction::MODE_DRAG_DROP,
            'template_html' => '<p>Summary {{1}} and {{2}}.</p>',
            'answer_rule' => 'one_word_only',
        ],
        'questions' => [
            ['number' => 1, 'prompt' => 'Blank 1'],
            ['number' => 2, 'prompt' => 'Blank 2'],
        ],
        'template_html' => '<p>Summary {{1}} and {{2}}.</p>',
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-dnd-dropzone--inline', false)
        ->assertSee('reading-dnd-pool', false);
});

it('allows admin to update interaction settings on a group', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $test = createDragDropReadingTest(['slug' => 'admin-dnd-settings']);
    $group = $test->passages->first()->groups->first();

    $this->actingAs($admin)
        ->put(route('admin.reading-question-groups.interaction-settings.update', $group), [
            'interaction_mode' => ReadingGroupInteraction::MODE_SELECT,
            'allow_reuse' => '1',
        ])
        ->assertRedirect();

    $group->refresh();
    expect($group->settings['interaction_mode'])->toBe(ReadingGroupInteraction::MODE_SELECT);
    expect($group->settings['allow_reuse'])->toBeTrue();
});
