<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Listening\ListeningQuestionBuilderService;

beforeEach(function (): void {
    seedRbac();
});

function createQuestionBuilderAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-questions-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function createListeningTestForQuestions(User $admin, array $overrides = []): ListeningTest
{
    return ListeningTest::query()->create(array_merge([
        'title' => 'Question Builder Test '.uniqid(),
        'slug' => 'question-builder-'.uniqid(),
        'test_code' => 'LQB-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));
}

function createSectionForQuestionBuilder(ListeningTest $test, int $sectionNumber = 1): ListeningSection
{
    $ranges = [1 => [1, 10], 2 => [11, 20], 3 => [21, 30], 4 => [31, 40]];

    return ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => $sectionNumber,
        'title' => 'Section '.$sectionNumber,
        'section_type' => ListeningSectionType::Conversation->value,
        'start_question_number' => $ranges[$sectionNumber][0],
        'end_question_number' => $ranges[$sectionNumber][1],
        'total_questions' => 10,
        'display_order' => $sectionNumber,
        'is_active' => true,
    ]);
}

function validGroupPayload(int $start = 1, int $end = 5, array $overrides = []): array
{
  $lines = [];

  for ($i = $start; $i <= $end; $i++) {
      $lines[] = "Field {$i}: [blank:{$i}]";
  }

    return array_merge([
        'title' => 'Group '.$start.'-'.$end,
        'question_type' => ListeningQuestionType::FormCompletion->value,
        'start_question_number' => $start,
        'end_question_number' => $end,
        'layout_type' => ListeningLayoutType::Form->value,
        'content' => implode("\n", $lines),
        'settings' => ['word_limit' => 2, 'template_type' => 'form'],
        'is_active' => true,
    ], $overrides);
}

function validQuestionPayload(int $number = 1, array $overrides = []): array
{
    return array_merge([
        'question_number' => $number,
        'question_type' => ListeningQuestionType::FormCompletion->value,
        'answer_format' => ListeningAnswerFormat::Text->value,
        'correct_answer' => [['value' => 'library', 'type' => 'text']],
        'word_limit' => 2,
        'marks' => 1,
        'is_active' => true,
        'is_required' => true,
    ], $overrides);
}

it('allows admin to view question builder dashboard', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.builder.index', $test))
        ->assertOk()
        ->assertSee('Listening Test Builder')
        ->assertSee('Sections & Question Groups');
});

it('allows admin to create question group', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), validGroupPayload())
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestionGroup::query()->where('listening_section_id', $section->id)->count())->toBe(1);
});

it('rejects group range outside section range', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.create', [$test, $section]))
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), validGroupPayload(1, 11))
        ->assertRedirect()
        ->assertSessionHasErrors('end_question_number');
});

it('rejects overlapping group range', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), validGroupPayload(1, 5))
        ->assertRedirect();

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.create', [$test, $section]))
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), validGroupPayload(4, 8))
        ->assertRedirect()
        ->assertSessionHasErrors('end_question_number');
});

it('allows admin to update question group', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 3), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 3,
    ]));

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.sections.groups.update', [$test, $section, $group]), validGroupPayload(1, 3, ['title' => 'Updated Group']))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($group->fresh()?->title)->toBe('Updated Group');
});

it('allows admin to delete question group', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 2), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 2,
    ]));

    $this->actingAs($admin)
        ->delete(route('admin.listening.tests.sections.groups.destroy', [$test, $section, $group]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestionGroup::withTrashed()->find($group->id)?->trashed())->toBeTrue();
});

it('allows admin to quick create blank question group', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.store-blank', [$test, $section]))
        ->assertRedirect()
        ->assertSessionHas('status');

    $group = ListeningQuestionGroup::query()->where('listening_section_id', $section->id)->first();

    expect($group)->not->toBeNull()
        ->and($group->start_question_number)->toBe(1)
        ->and($group->end_question_number)->toBe(4)
        ->and($group->question_type)->toBe(ListeningQuestionType::FormCompletion);
});

it('duplicates question group without copying questions', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 4), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 4,
    ]));

    ListeningQuestion::query()->create(array_merge(validQuestionPayload(1), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
    ]));

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.duplicate', [$test, $section, $group]))
        ->assertRedirect()
        ->assertSessionHas('status');

    $copy = ListeningQuestionGroup::query()
        ->where('listening_section_id', $section->id)
        ->where('id', '!=', $group->id)
        ->first();

    expect($copy)->not->toBeNull()
        ->and($copy->title)->toBe($group->title)
        ->and($copy->start_question_number)->toBe(5)
        ->and($copy->end_question_number)->toBe(8)
        ->and($copy->questions()->count())->toBe(0);
});

it('allows admin to create question inside group', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 5), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 5,
    ]));

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(1))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('question_number', 1)->exists())->toBeTrue();
});

it('renders objective question builder when questions exist', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–3',
        'question_type' => ListeningQuestionType::MCQ,
        'start_question_number' => 1,
        'end_question_number' => 3,
        'total_questions' => 3,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [
            ['key' => 'A', 'text' => 'Option A'],
            ['key' => 'B', 'text' => 'Option B'],
        ],
        'is_active' => true,
    ]);
    ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 1,
        'question_type' => ListeningQuestionType::MCQ,
        'question_text' => 'Sample MCQ prompt',
        'answer_format' => ListeningAnswerFormat::Letter,
        'correct_answer' => [['value' => 'A', 'type' => 'letter']],
        'accepted_answers' => [],
        'marks' => 1,
        'display_order' => 1,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.listening-question-groups.objective-questions.index', $group))
        ->assertOk()
        ->assertSee('Sample MCQ prompt');
});

it('rejects duplicate question number', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 5), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 5,
    ]));

    $this->actingAs($admin)->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(1));
    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.questions.create', [$test, $section, $group]))
        ->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(1))
        ->assertRedirect()
        ->assertSessionHasErrors('question_number');
});

it('rejects question outside group range', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 5), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 5,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.questions.create', [$test, $section, $group]))
        ->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(8))
        ->assertRedirect()
        ->assertSessionHasErrors('question_number');
});

it('rejects question outside section range', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 5), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 5,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.questions.create', [$test, $section, $group]))
        ->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(15))
        ->assertRedirect()
        ->assertSessionHasErrors('question_number');
});

it('requires correct answer unless draft config allows', function (): void {
    config(['listening.questions.allow_draft_without_answer' => false]);

    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 3), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 3,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.questions.create', [$test, $section, $group]))
        ->post(route('admin.listening.tests.sections.groups.questions.store', [$test, $section, $group]), validQuestionPayload(1, [
            'correct_answer' => [],
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('correct_answer');
});

it('allows admin to bulk create questions from group range', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 3), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 3,
    ]));

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.questions.bulk-create', [$test, $section, $group]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($group->questions()->count())->toBe(3);
});

it('bulk create skips existing questions', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 3), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 3,
    ]));

    $this->actingAs($admin)->post(route('admin.listening.tests.sections.groups.questions.bulk-create', [$test, $section, $group]));
    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.questions.bulk-create', [$test, $section, $group]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($group->questions()->count())->toBe(3);
});

it('allows admin to reorder questions', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 2), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 2,
    ]));

    $this->actingAs($admin)->post(route('admin.listening.tests.sections.groups.questions.bulk-create', [$test, $section, $group]));
    $ids = $group->questions()->orderByDesc('question_number')->pluck('id')->all();

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.questions.reorder', [$test, $section, $group]), [
            'questions' => $ids,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');
});

it('shows missing numbers in builder summary', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $summary = app(ListeningQuestionBuilderService::class)->getTestBuilderSummary($test);

    expect($summary['missing_numbers'])->not->toBeEmpty();
    expect($summary['sections'][0]['section_number'])->toBe(1);
});

it('publish action rejects incomplete questions', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);

    for ($i = 1; $i <= 4; $i++) {
        createSectionForQuestionBuilder($test, $i);
    }

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.publish', $test))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('section index links to unified question builder', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.sections.index', $test))
        ->assertOk()
        ->assertSee('Section Builder')
        ->assertSee('Add Question Group');

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.sections.builder.index', [$test, $section]))
        ->assertRedirect(route('admin.listening.tests.builder.index', ['listeningTest' => $test, 'section' => $section->id]));

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.builder.index', ['listeningTest' => $test, 'section' => $section->id]))
        ->assertOk()
        ->assertSee('Section '.$section->section_number);
});

it('forbids unauthorized user from managing question builder', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'student-qb-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
    $test = createListeningTestForQuestions(createQuestionBuilderAdmin());

    $this->actingAs($student)
        ->get(route('admin.listening.tests.builder.index', $test))
        ->assertForbidden();
});

it('updates sentence completion group metadata without type payload fields', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the sentences below.',
        'question_type' => ListeningQuestionType::SentenceCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.sections.groups.update', [$test, $section, $group]), [
            'title' => 'Updated Sentences 1–4',
            'instruction' => 'Complete the sentences below. Write ONE WORD AND/OR A NUMBER.',
            'question_type' => ListeningQuestionType::SentenceCompletion->value,
            'start_question_number' => 1,
            'end_question_number' => 4,
            'layout_type' => ListeningLayoutType::Default->value,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($group->fresh()?->title)->toBe('Updated Sentences 1–4');
});

it('stores sentence completion question from manual builder rows', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the sentences below.',
        'question_type' => ListeningQuestionType::SentenceCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.store', $group), [
            'question_number' => 1,
            'sentence_before' => 'The first bridge was built in',
            'sentence_after' => 'during the nineteenth century.',
            'correct_answer' => '1820',
            'difficulty' => 'medium',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $question = ListeningQuestion::query()->where('listening_question_group_id', $group->id)->first();

    expect($question)->not->toBeNull()
        ->and($question->question_number)->toBe(1)
        ->and($question->question_text)->toContain('The first bridge was built in')
        ->and($question->question_text)->toContain('during the nineteenth century.');
});

it('returns reading compatible live detect json for completion builder', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 3), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 3,
    ]));

    $this->actingAs($admin)
        ->postJson(route('admin.listening-question-groups.completion-questions.detect', $group), [
            'content' => '<p>Field {{1}} and {{2}}</p>',
        ])
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('valid', true);
});

it('syncs completion questions from brace placeholders on template save', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), [
            'answer_rule' => 'one_word_only',
            'template_html' => '<p>Home address: {{1}} Reason: {{2}} Type: {{3}} Fee: {{4}}</p>',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(4);
});

it('allows re-saving completion template without duplicate question errors', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $payload = [
        'answer_rule' => 'one_word_only',
        'template_html' => '<p>Home address: {{1}} Reason: {{2}} Type: {{3}} Fee: {{4}}</p>',
    ];

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), $payload)
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), $payload)
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(4);
});

it('restores soft-deleted completion questions when template is saved again', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $payload = [
        'answer_rule' => 'one_word_only',
        'template_html' => '<p>Home address: {{1}} Reason: {{2}} Type: {{3}} Fee: {{4}}</p>',
    ];

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), $payload)
        ->assertRedirect()
        ->assertSessionHas('status');

    $question = ListeningQuestion::query()->where('listening_question_group_id', $group->id)->where('question_number', 1)->firstOrFail();
    $question->delete();

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(3)
        ->and(ListeningQuestion::withTrashed()->where('listening_question_group_id', $group->id)->count())->toBe(4);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), $payload)
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(4);
});

it('reclaims soft-deleted questions from another group when saving completion template', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $otherGroup = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Old Group 1–1',
        'question_type' => ListeningQuestionType::FormCompletion,
        'start_question_number' => 1,
        'end_question_number' => 1,
        'total_questions' => 1,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);
    $orphaned = ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $otherGroup->id,
        'question_number' => 1,
        'question_type' => ListeningQuestionType::FormCompletion,
        'answer_format' => ListeningAnswerFormat::Text,
        'correct_answer' => [],
        'accepted_answers' => [],
        'marks' => 1,
        'display_order' => 1,
        'is_required' => true,
        'is_active' => true,
    ]);
    $orphaned->delete();

    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), [
            'answer_rule' => 'one_word_only',
            'template_html' => '<p>Home address: {{1}} Reason: {{2}} Type: {{3}} Fee: {{4}}</p>',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(4)
        ->and(ListeningQuestion::query()->where('question_number', 1)->value('listening_question_group_id'))->toBe($group->id);
});

it('opens group editor when question_group query param is present', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 4), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 4,
    ]));

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.builder.index', [
            'listeningTest' => $test,
            'section' => $section->id,
            'question_group' => $group->id,
        ]))
        ->assertOk()
        ->assertSee('Question Group Editor')
        ->assertSee('Save Question Group');
});

it('opens group editor when question_group query key is malformed', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create(array_merge(validGroupPayload(1, 4), [
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'total_questions' => 4,
    ]));

    $this->actingAs($admin)
        ->get('/admin/listening/tests/'.$test->id.'/builder?section='.$section->id.'&amp;question_group='.$group->id)
        ->assertOk()
        ->assertSee('Question Group Editor')
        ->assertSee('Save Question Group');
});

it('updates summary completion group metadata without template content', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.sections.groups.update', [$test, $section, $group]), [
            'title' => 'Summary Questions 1–4',
            'instruction' => 'Complete the summary below.',
            'question_type' => ListeningQuestionType::SummaryCompletion->value,
            'start_question_number' => 1,
            'end_question_number' => 4,
            'layout_type' => ListeningLayoutType::Default->value,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($group->fresh()?->title)->toBe('Summary Questions 1–4');
});

it('syncs table completion questions from json table_data payload', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–2',
        'instruction' => 'Complete the table.',
        'question_type' => ListeningQuestionType::TableCompletion,
        'start_question_number' => 1,
        'end_question_number' => 2,
        'total_questions' => 2,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $tableData = json_encode([
        'rows' => [
            [
                'is_header' => true,
                'cells' => [
                    ['content' => 'Country', 'is_blank' => false, 'blank_number' => 0],
                    ['content' => 'Population', 'is_blank' => false, 'blank_number' => 0],
                ],
            ],
            [
                'is_header' => false,
                'cells' => [
                    ['content' => 'France', 'is_blank' => false, 'blank_number' => 0],
                    ['content' => '', 'is_blank' => true, 'blank_number' => 1],
                ],
            ],
            [
                'is_header' => false,
                'cells' => [
                    ['content' => 'Spain', 'is_blank' => false, 'blank_number' => 0],
                    ['content' => '', 'is_blank' => true, 'blank_number' => 2],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.table', $group), [
            'answer_rule' => 'one_word_only',
            'table_data' => $tableData,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(2);
});

it('syncs form completion questions from single-brace placeholders', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–2',
        'instruction' => 'Complete the form.',
        'question_type' => ListeningQuestionType::FormCompletion,
        'start_question_number' => 1,
        'end_question_number' => 2,
        'total_questions' => 2,
        'layout_type' => ListeningLayoutType::Form,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), [
            'answer_rule' => 'one_word_only',
            'template_html' => '<p>Street: {1} Town: {2}</p>',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->count())->toBe(2);
});

it('stores matching options without strict group validation failure', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Match each item.',
        'question_type' => ListeningQuestionType::Matching,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'options' => ['items' => [], 'choices' => [], 'allow_choice_reuse' => false],
        'is_active' => true,
    ]);

    foreach (['A', 'B', 'C', 'D'] as $key) {
        $this->actingAs($admin)
            ->post(route('admin.listening-question-groups.matching.options.store', $group), [
                'option_key' => $key,
                'option_label' => "Option {$key}",
            ])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    $group->refresh();

    expect($group->options['choices'] ?? [])->toHaveCount(4);
});

it('uploads labelling diagram image without group validation failure', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 5–8',
        'instruction' => 'Label the map.',
        'question_type' => ListeningQuestionType::MapLabelling,
        'start_question_number' => 5,
        'end_question_number' => 8,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Map,
        'is_active' => true,
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->create('map.jpg', 100, 'image/jpeg');

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.labelling-questions.upload', $group), [
            'diagram_image' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $group->refresh();

    expect($group->image_path)->not->toBeNull()
        ->and($group->options['image']['path'] ?? null)->toBe($group->image_path);
});

it('persists completion alternative answers on update', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Complete the summary.',
        'question_type' => ListeningQuestionType::SummaryCompletion,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.template', $group), [
            'answer_rule' => 'one_word_only',
            'template_html' => '<p>Answer: {{1}}</p>',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $question = ListeningQuestion::query()->where('listening_question_group_id', $group->id)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.listening-completion-questions.update', $question->id), [
            'question_number' => 1,
            'prompt' => 'Answer: _________',
            'correct_answer' => 'Paris',
            'alternative_answers' => ['City of Light', 'PARIS'],
            'difficulty' => 'medium',
            'case_sensitive' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $question->refresh();

    expect($question->accepted_answers)->toHaveCount(2)
        ->and(collect($question->accepted_answers)->pluck('value')->all())->toBe(['City of Light', 'PARIS']);
});

it('stores sentence completion questions in manual mode', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 5–8',
        'instruction' => 'Complete the sentences.',
        'question_type' => ListeningQuestionType::SentenceCompletion,
        'start_question_number' => 5,
        'end_question_number' => 8,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.completion-questions.store', $group), [
            'question_number' => 5,
            'sentence_before' => 'The first bridge was built in',
            'sentence_after' => 'during the nineteenth century.',
            'correct_answer' => '1850',
            'difficulty' => 'medium',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningQuestion::query()->where('listening_question_group_id', $group->id)->where('question_number', 5)->exists())->toBeTrue();
});

it('stores short answer questions', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Answer the questions.',
        'question_type' => ListeningQuestionType::ShortAnswer,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.short-answer-questions.store', $group), [
            'answer_rule' => 'three_words',
            'question_number' => 1,
            'prompt' => 'What is the main purpose of the research?',
            'correct_answer' => 'water quality',
            'alternative_answers' => ['water-quality'],
            'difficulty' => 'medium',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $question = ListeningQuestion::query()->where('listening_question_group_id', $group->id)->firstOrFail();

    expect($question->question_text)->toBe('What is the main purpose of the research?')
        ->and($question->accepted_answers)->toHaveCount(1);
});

it('renders matching builder when options exist', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 1–4',
        'instruction' => 'Match each item.',
        'question_type' => ListeningQuestionType::Matching,
        'start_question_number' => 1,
        'end_question_number' => 4,
        'total_questions' => 4,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [
            'choices' => [
                ['key' => 'A', 'text' => 'Option A'],
                ['key' => 'B', 'text' => 'Option B'],
            ],
            'items' => [],
            'allow_choice_reuse' => false,
        ],
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.listening-question-groups.matching-questions.index', $group))
        ->assertOk()
        ->assertSee('Option A')
        ->assertSee('Option B');
});

it('stores matching questions when choices exist without separate matching items', function (): void {
    $admin = createQuestionBuilderAdmin();
    $test = createListeningTestForQuestions($admin);
    $section = createSectionForQuestionBuilder($test, 1);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 5–9',
        'instruction' => 'Match each item.',
        'question_type' => ListeningQuestionType::Matching,
        'start_question_number' => 5,
        'end_question_number' => 9,
        'total_questions' => 5,
        'layout_type' => ListeningLayoutType::Default,
        'options' => ['items' => [], 'choices' => [], 'allow_choice_reuse' => false],
        'is_active' => true,
    ]);

    foreach ([
        ['A', 'Option 1'],
        ['B', 'Option 2'],
        ['C', 'Option 3'],
        ['D', 'Option 4'],
        ['E', 'Option 5'],
    ] as [$key, $label]) {
        $this->actingAs($admin)
            ->post(route('admin.listening-question-groups.matching.options.store', $group), [
                'option_key' => $key,
                'option_label' => $label,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    $this->actingAs($admin)
        ->post(route('admin.listening-question-groups.matching.questions.store', $group), [
            'question_number' => 5,
            'prompt' => 'Blank 5',
            'correct_answer' => 'A',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    $question = ListeningQuestion::query()
        ->where('listening_question_group_id', $group->id)
        ->where('question_number', 5)
        ->firstOrFail();

    expect($question->question_text)->toBe('Blank 5')
        ->and($question->correct_answer[0]['value'] ?? null)->toBe('A');

    $group->refresh();

    expect($group->options['choices'] ?? [])->toHaveCount(5)
        ->and($group->options['items'] ?? [])->toHaveCount(1);

    $this->actingAs($admin)
        ->get(route('admin.listening-question-groups.matching-questions.index', $group))
        ->assertOk()
        ->assertSee('Option 1')
        ->assertSee('Blank 5');
});
