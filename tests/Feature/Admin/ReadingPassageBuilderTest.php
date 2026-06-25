<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingTest;
use App\Support\Reading\ReadingPassageContentRenderer;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createReadingTestForPassageBuilder(): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'passage-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    return ReadingTest::query()->create([
        'title' => 'Passage Builder Test',
        'slug' => 'passage-builder-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
}

function latestPassage(ReadingTest $test): ReadingPassage
{
    return ReadingPassage::query()
        ->where('reading_test_id', $test->id)
        ->orderByDesc('id')
        ->firstOrFail();
}
function passagePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Materials to take us beyond concrete',
        'subtitle' => 'Academic',
        'instruction' => 'You should spend about 20 minutes on Questions 1-13.',
        'start_question' => 1,
        'end_question' => 13,
        'content_html' => '<p>Concrete has shaped modern cities.</p><p>Researchers are exploring sustainable alternatives.</p>',
        'status' => PassageStatus::Draft->value,
        'auto_paragraph_labels' => true,
    ], $overrides);
}

it('renders passage builder with sidebar and editor', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Passage Editor')
        ->assertSee('Add Passage');
});

it('creates blank passage from builder and opens editor', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->post(route('admin.reading-tests.passages.store', $test))
        ->assertRedirect();

    $passage = $test->passages()->first();

    expect($passage)->not->toBeNull();
    expect($passage->part_number)->toBe(1);
    expect($passage->start_question)->toBe(1);
    expect($passage->end_question)->toBe(13);
});

it('supports adding three passages with sequential question ranges', function (): void {
    $test = createReadingTestForPassageBuilder();

    foreach ([
        ['title' => 'Passage One', 'start_question' => 1, 'end_question' => 13],
        ['title' => 'Passage Two', 'start_question' => 14, 'end_question' => 26],
        ['title' => 'Passage Three', 'start_question' => 27, 'end_question' => 40],
    ] as $data) {
        $this->post(route('admin.reading-tests.passages.store', $test));
        $passage = latestPassage($test);

        $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload($data))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    expect($test->passages()->count())->toBe(3);
    expect($test->passages()->orderBy('part_number')->pluck('title')->all())->toBe([
        'Passage One',
        'Passage Two',
        'Passage Three',
    ]);
});

it('updates passage content and persists after refresh', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->post(route('admin.reading-tests.passages.store', $test));
    $passage = $test->passages()->firstOrFail();

    $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload([
        'content_html' => '<p>Updated passage content.</p>',
    ]))->assertRedirect();

    $passage->refresh();

    expect($passage->content_html)->toContain('Updated passage content');
    expect($passage->content_text)->toContain('Updated passage content');

    $this->get(route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id]))
        ->assertOk()
        ->assertSee('Updated passage content', false);
});

it('duplicates passage without questions by default', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->post(route('admin.reading-tests.passages.store', $test));
    $passage = $test->passages()->firstOrFail();

    $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload());
    $passage->refresh();

    $group = $passage->groups()->create([
        'title' => 'Group 1',
        'instruction' => 'Answer questions.',
        'question_type' => 'true_false_not_given',
        'start_question' => 1,
        'end_question' => 1,
        'sort_order' => 1,
    ]);

    $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'Sample question',
        'marks' => 1,
        'sort_order' => 1,
    ]);

    $this->post(route('admin.reading-tests.passages.duplicate', [$test, $passage]))
        ->assertRedirect();

    expect($test->passages()->count())->toBe(2);

    $copy = $test->passages()->whereKeyNot($passage->id)->firstOrFail();

    expect($copy->title)->toBe('Copy of '.$passage->title);
    expect($copy->groups()->count())->toBe(0);
    expect($copy->start_question)->toBe(14);
    expect($copy->end_question)->toBe(26);
});

it('reorders passages and renumbers part numbers', function (): void {
    $test = createReadingTestForPassageBuilder();

    foreach (['Alpha', 'Beta', 'Gamma'] as $title) {
        $this->post(route('admin.reading-tests.passages.store', $test));
        $passage = latestPassage($test);

        $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload([
            'title' => $title,
            'start_question' => match ($title) {
                'Alpha' => 1,
                'Beta' => 14,
                'Gamma' => 27,
            },
            'end_question' => match ($title) {
                'Alpha' => 13,
                'Beta' => 26,
                'Gamma' => 40,
            },
        ]))->assertRedirect();
    }

    $test->refresh();

    $ids = $test->passages()->ordered()->pluck('id')->all();
    $reordered = [$ids[2], $ids[0], $ids[1]];

    $this->post(route('admin.reading-tests.passages.reorder', $test), [
        'passage_ids' => $reordered,
    ])->assertRedirect();

    expect($test->passages()->ordered()->pluck('title')->all())->toBe(['Gamma', 'Alpha', 'Beta']);
    expect($test->passages()->ordered()->pluck('part_number')->all())->toBe([1, 2, 3]);
});

it('moves passage up and down', function (): void {
    $test = createReadingTestForPassageBuilder();

    foreach (['First', 'Second'] as $index => $title) {
        $this->post(route('admin.reading-tests.passages.store', $test));
        $passage = latestPassage($test);

        $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload([
            'title' => $title,
            'start_question' => $index === 0 ? 1 : 14,
            'end_question' => $index === 0 ? 13 : 26,
        ]))->assertRedirect();
    }

    $test->refresh();
    $second = $test->passages()->where('title', 'Second')->firstOrFail();

    $this->post(route('admin.reading-tests.passages.move-up', [$test, $second]))
        ->assertRedirect();

    expect($test->passages()->ordered()->pluck('title')->all())->toBe(['Second', 'First']);
});

it('deletes passage and cascades question groups', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->post(route('admin.reading-tests.passages.store', $test));
    $passage = $test->passages()->firstOrFail();

    $this->put(route('admin.reading-tests.passages.update', [$test, $passage]), passagePayload());

    $group = $passage->groups()->create([
        'title' => 'Group 1',
        'instruction' => 'Answer questions.',
        'question_type' => 'true_false_not_given',
        'start_question' => 1,
        'end_question' => 1,
        'sort_order' => 1,
    ]);

    $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'Sample question',
        'marks' => 1,
        'sort_order' => 1,
    ]);

    $this->delete(route('admin.reading-tests.passages.destroy', [$test, $passage]))
        ->assertRedirect(route('admin.reading-tests.builder', $test));

    expect(ReadingPassage::query()->whereKey($passage->id)->exists())->toBeFalse();
    expect($test->passages()->count())->toBe(0);
});

it('rejects overlapping question ranges', function (): void {
    $test = createReadingTestForPassageBuilder();

    $this->post(route('admin.reading-tests.passages.store', $test));
    $first = $test->passages()->firstOrFail();

    $this->put(route('admin.reading-tests.passages.update', [$test, $first]), passagePayload([
        'start_question' => 1,
        'end_question' => 13,
    ]))->assertRedirect();

    $this->post(route('admin.reading-tests.passages.store', $test));
    $second = $test->passages()->orderByDesc('id')->firstOrFail();

    $this->put(route('admin.reading-tests.passages.update', [$test, $second]), passagePayload([
        'title' => 'Overlapping Passage',
        'start_question' => 10,
        'end_question' => 20,
    ]))->assertSessionHasErrors('start_question');
});

it('applies auto paragraph labels in rendered content', function (): void {
    $html = '<p>First paragraph.</p><p>Second paragraph.</p>';
    $rendered = ReadingPassageContentRenderer::applyParagraphLabels($html);

    expect($rendered)->toContain('reading-passage-label');
    expect($rendered)->toContain('data-paragraph="A"');
    expect($rendered)->toContain('data-paragraph="B"');
    expect($rendered)->toContain('>A<');
    expect($rendered)->toContain('>B<');
});

it('strips broken passage reference markers from rendered content', function (): void {
    $html = '<p>{[The problem with replacing concrete is that it is so very good at what it does.]}10}]</p>';
    $rendered = ReadingPassageContentRenderer::applyParagraphLabels($html);

    expect($rendered)->toContain('The problem with replacing concrete');
    expect($rendered)->not->toContain('{[');
    expect($rendered)->not->toContain('}]');
});

it('strips bracketed question id reference markers from rendered content', function (): void {
    $html = '<p>Mix. {[Iron-ore slag, a byproduct of the iron-ore smelting process, can be used in a similar way.}[1]</p>';
    $rendered = ReadingPassageContentRenderer::applyParagraphLabels($html);

    expect($rendered)->toContain('Iron-ore slag, a byproduct of the iron-ore smelting process');
    expect($rendered)->not->toContain('{[');
    expect($rendered)->not->toContain('}[1]');
});

it('strips reference markers with double closing brackets', function (): void {
    $html = '<p>B {[The problem with replacing concrete is that it is so very good at what it does. Chris Cheeseman,][10]]} more text.</p>';
    $rendered = ReadingPassageContentRenderer::sanitizeReferenceMarkers($html);

    expect($rendered)->toContain('Chris Cheeseman');
    expect($rendered)->toContain('more text.');
    expect($rendered)->not->toContain('{[');
    expect($rendered)->not->toContain('[10]');
});

it('opens builder after creating a reading test', function (): void {
    $this->withoutVite();

    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-builder-ui@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Builder UI Test',
        'slug' => 'builder-ui-test',
        'exam_type' => ExamType::Academic->value,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft->value,
    ]);

    $test = ReadingTest::query()->where('slug', 'builder-ui-test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Reading Test Builder')
        ->assertSee('Add Passage')
        ->assertSee('Builder UI Test');
});
