<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
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

function studentWithReadingModule(): \App\Models\User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-renderer-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-renderer-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createPublishedReadingRendererTest(array $overrides = []): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'renderer-seed-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    $slug = $overrides['slug'] ?? 'reading-renderer-'.uniqid();

    $test = ReadingTest::query()->create(array_merge([
        'title' => $overrides['title'] ?? 'Renderer Test',
        'slug' => $slug,
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'instructions' => 'Read all passages carefully.',
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));

    $passage = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 1,
        'title' => $overrides['passage_title'] ?? 'Unique Passage Title '.$slug,
        'subtitle' => 'Subtitle for '.$slug,
        'instruction' => 'Answer all questions.',
        'start_question' => 1,
        'end_question' => 5,
        'content_html' => '<p>Passage body for <strong>'.$slug.'</strong>.</p>',
        'content_text' => 'Passage body for '.$slug.'.',
        'status' => PassageStatus::Published,
        'settings' => ['auto_paragraph_labels' => true],
        'sort_order' => 1,
    ]);

    $group = ReadingQuestionGroup::query()->create([
        'passage_id' => $passage->id,
        'title' => 'Questions 1–5',
        'instruction' => $overrides['group_instruction'] ?? 'Do the following statements agree?',
        'question_type' => $overrides['question_type'] ?? OfficialReadingQuestionType::TrueFalseNotGiven,
        'start_question' => 1,
        'end_question' => 5,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
        'settings' => $overrides['group_settings'] ?? [],
    ]);

    if (isset($overrides['options'])) {
        foreach ($overrides['options'] as $index => $option) {
            $group->groupOptions()->create([
                'option_key' => $option['key'],
                'option_label' => $option['label'] ?? '',
                'sort_order' => $index + 1,
            ]);
        }
    }

    if (isset($overrides['questions'])) {
        foreach ($overrides['questions'] as $index => $questionData) {
            $question = $group->questions()->create([
                'question_number' => $questionData['number'],
                'prompt' => $questionData['prompt'],
                'paragraph_reference' => $questionData['paragraph_reference'] ?? null,
                'marks' => 1,
                'sort_order' => $index + 1,
            ]);

            if (isset($questionData['options'])) {
                foreach ($questionData['options'] as $optIndex => $opt) {
                    $question->options()->create([
                        'option_key' => $opt['key'],
                        'option_label' => $opt['label'],
                        'sort_order' => $optIndex + 1,
                    ]);
                }
            }
        }
    } else {
        for ($n = 1; $n <= 5; $n++) {
            $group->questions()->create([
                'question_number' => $n,
                'prompt' => "Statement {$n} for {$slug}",
                'marks' => 1,
                'sort_order' => $n,
            ]);
        }
    }

    return $test->fresh();
}

it('lists published reading tests', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest(['slug' => 'list-reading-test-a', 'title' => 'List Test Alpha']);

    $this->actingAs($student)
        ->get(route('reading-tests.index'))
        ->assertOk()
        ->assertSee('List Test Alpha')
        ->assertSee('Open');
});

it('opens two different published tests by slug with distinct content', function (): void {
    $student = studentWithReadingModule();

    $testA = createPublishedReadingRendererTest([
        'slug' => 'renderer-test-alpha',
        'title' => 'Renderer Alpha',
        'passage_title' => 'Alpha Passage Heading',
    ]);

    $testB = createPublishedReadingRendererTest([
        'slug' => 'renderer-test-beta',
        'title' => 'Renderer Beta',
        'passage_title' => 'Beta Passage Heading',
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $testA))
        ->assertOk()
        ->assertSee('Renderer Alpha')
        ->assertSee('Alpha Passage Heading')
        ->assertSee('Statement 1 for renderer-test-alpha')
        ->assertDontSee('Beta Passage Heading');

    $this->actingAs($student)
        ->get(route('reading-tests.start', $testB))
        ->assertOk()
        ->assertSee('Renderer Beta')
        ->assertSee('Beta Passage Heading')
        ->assertSee('Statement 1 for renderer-test-beta')
        ->assertDontSee('Alpha Passage Heading');
});

it('returns 404 for draft reading tests', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest(['slug' => 'draft-renderer-test']);
    $test->update(['status' => PublishStatus::Draft, 'published_at' => null]);

    $this->actingAs($student)
        ->get(route('reading-tests.show', $test))
        ->assertNotFound();

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertNotFound();
});

it('renders full CBT shell with part navigator and data attributes', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest(['slug' => 'cbt-shell-test', 'title' => 'CBT Shell Test']);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-test-cbt', false)
        ->assertSee('reading-test-footer', false)
        ->assertSee('Part 1')
        ->assertSee('Review')
        ->assertSee('Submit')
        ->assertSee('data-test-id="'.$test->id.'"', false)
        ->assertSee('data-question-type="true_false_not_given"', false);
});

it('renders matching information as grid table', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'matching-info-renderer',
        'question_type' => OfficialReadingQuestionType::MatchingInformation,
        'options' => [
            ['key' => 'A', 'label' => ''],
            ['key' => 'B', 'label' => ''],
            ['key' => 'C', 'label' => ''],
        ],
        'questions' => [
            ['number' => 1, 'prompt' => 'Grid statement one'],
            ['number' => 2, 'prompt' => 'Grid statement two'],
        ],
        'group_settings' => [],
    ]);

    $response = $this->actingAs($student)->get(route('reading-tests.start', $test));

    $response->assertOk()
        ->assertSee('reading-test-matching-grid', false)
        ->assertSee('Grid statement one')
        ->assertSee('data-question-type="matching_information"', false);
});

it('renders matching headings with heading list and selects', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'matching-headings-renderer',
        'question_type' => OfficialReadingQuestionType::MatchingHeadings,
        'options' => [
            ['key' => 'i', 'label' => 'First heading'],
            ['key' => 'ii', 'label' => 'Second heading'],
        ],
        'questions' => [
            ['number' => 1, 'prompt' => 'Paragraph A', 'paragraph_reference' => 'A'],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('List of Headings')
        ->assertSee('First heading')
        ->assertSee('reading-test-select', false);
});

it('renders true false not given as radio table', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'tfng-renderer',
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven,
        'questions' => [
            ['number' => 1, 'prompt' => 'TFNG statement here'],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('TRUE')
        ->assertSee('FALSE')
        ->assertSee('NOT GIVEN')
        ->assertSee('TFNG statement here');
});

it('renders mcq single with radio options', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'mcq-single-renderer',
        'question_type' => OfficialReadingQuestionType::MultipleChoiceSingle,
        'questions' => [
            [
                'number' => 1,
                'prompt' => 'Choose the best answer',
                'options' => [
                    ['key' => 'A', 'label' => 'Option A text'],
                    ['key' => 'B', 'label' => 'Option B text'],
                ],
            ],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('Option A text')
        ->assertSee('type="radio"', false);
});

it('renders summary completion inline blanks from template', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'summary-completion-renderer',
        'question_type' => OfficialReadingQuestionType::SummaryCompletion,
        'group_settings' => [
            'template_html' => '<p>The study found that {{1}} was essential and {{2}} was rare.</p>',
            'answer_rule' => 'one_word_only',
        ],
        'questions' => [
            ['number' => 1, 'prompt' => ''],
            ['number' => 2, 'prompt' => ''],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('reading-test-blank', false)
        ->assertSee('data-question-number="1"', false)
        ->assertSee('data-question-number="2"', false)
        ->assertSee('The study found that', false);
});

it('renders short answer inputs', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'short-answer-renderer',
        'question_type' => OfficialReadingQuestionType::ShortAnswer,
        'questions' => [
            ['number' => 1, 'prompt' => 'What is the main topic?'],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('What is the main topic?')
        ->assertSee('reading-test-short-answer', false);
});

it('shows test intro page with start link', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest(['slug' => 'intro-page-test', 'title' => 'Intro Page Test']);

    $this->actingAs($student)
        ->get(route('reading-tests.show', $test))
        ->assertOk()
        ->assertSee('Intro Page Test')
        ->assertSee(route('reading-tests.start', $test), false);
});

it('opens volume 5 test via legacy exam reading url', function (): void {
    $student = studentWithReadingModule();
    $test = createPublishedReadingRendererTest([
        'slug' => 'reading-test-1',
        'title' => 'Reading Test 1',
        'passage_title' => 'Admin Passage One',
    ]);

    $this->actingAs($student)
        ->get(route('exam.reading.show', $test))
        ->assertOk()
        ->assertSee('Reading Test 1')
        ->assertSee('Admin Passage One')
        ->assertSee('reading-test-cbt', false);
});

it('supports three passages with question ranges in footer', function (): void {
    $student = studentWithReadingModule();
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Three Part Test',
        'slug' => 'three-part-renderer',
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    foreach ([
        ['part' => 1, 'title' => 'Part One Passage', 'start' => 1, 'end' => 2],
        ['part' => 2, 'title' => 'Part Two Passage', 'start' => 3, 'end' => 4],
        ['part' => 3, 'title' => 'Part Three Passage', 'start' => 5, 'end' => 6],
    ] as $index => $part) {
        $passage = $test->passages()->create([
            'part_number' => $part['part'],
            'title' => $part['title'],
            'start_question' => $part['start'],
            'end_question' => $part['end'],
            'content_html' => '<p>'.$part['title'].' content</p>',
            'status' => PassageStatus::Published,
            'sort_order' => $index + 1,
        ]);

        $group = $passage->groups()->create([
            'title' => "Q{$part['start']}-{$part['end']}",
            'question_type' => OfficialReadingQuestionType::ShortAnswer,
            'start_question' => $part['start'],
            'end_question' => $part['end'],
            'sort_order' => 1,
            'status' => PassageStatus::Published,
        ]);

        for ($n = $part['start']; $n <= $part['end']; $n++) {
            $group->questions()->create([
                'question_number' => $n,
                'prompt' => "Question {$n}",
                'marks' => 1,
                'sort_order' => $n,
            ]);
        }
    }

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('Part 1')
        ->assertSee('Part 2')
        ->assertSee('Part 3')
        ->assertSee('Part One Passage')
        ->assertSee('Part Two Passage')
        ->assertSee('Part Three Passage');
});
