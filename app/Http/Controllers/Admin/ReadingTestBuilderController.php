<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportReadingTestRequest;
use App\Http\Requests\Admin\SavePassageRequest;
use App\Http\Requests\Admin\SaveQuestionRequest;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\TestSection;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingTestBuilderController extends Controller
{
    public function __construct(private readonly ReadingTestBuilderService $builder)
    {
    }

    public function builder(ExamTest $readingTest): View
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('update', $readingTest);

        $module = $this->builder->readingModule($readingTest);
        $bank = $this->builder->questionBank($readingTest);
        $sections = $module->sections()
            ->with(['testQuestions.question.options', 'testQuestions.question.correctAnswer', 'testQuestions.question.explanation'])
            ->orderBy('sort_order')
            ->get();

        return view('pages.admin.reading-tests.builder', [
            'test' => $readingTest,
            'module' => $module,
            'bank' => $bank,
            'sections' => $sections,
            'questionTypes' => ReadingQuestionType::cases(),
            'statuses' => PublishStatus::cases(),
        ]);
    }

    public function preview(ExamTest $readingTest): View
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('view', $readingTest);

        $module = $this->builder->readingModule($readingTest);
        $sections = $module->sections()
            ->with(['testQuestions.question.options', 'testQuestions.question.correctAnswer'])
            ->orderBy('sort_order')
            ->get();

        return view('pages.admin.reading-tests.preview', [
            'test' => $readingTest,
            'sections' => $sections,
        ]);
    }

    public function storePassage(SavePassageRequest $request, ExamTest $readingTest): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $module = $this->builder->readingModule($readingTest);
        $this->builder->savePassage($module, $request->validated());

        return back()->with('status', 'Passage saved successfully.');
    }

    public function updatePassage(SavePassageRequest $request, ExamTest $readingTest, TestSection $section): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->ensureSectionBelongsToTest($readingTest, $section);
        $module = $this->builder->readingModule($readingTest);
        $this->builder->savePassage($module, $request->validated(), $section);

        return back()->with('status', 'Passage updated successfully.');
    }

    public function storeQuestion(SaveQuestionRequest $request, ExamTest $readingTest, TestSection $section): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->ensureSectionBelongsToTest($readingTest, $section);

        $module = $this->builder->readingModule($readingTest);
        $bank = $this->builder->questionBank($readingTest);
        $this->builder->saveQuestion(
            $readingTest,
            $module,
            $section,
            $bank,
            $request->validated()
        );

        return back()->with('status', 'Question added successfully.');
    }

    public function updateQuestion(SaveQuestionRequest $request, ExamTest $readingTest, Question $question): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);

        $pivot = $question->testQuestions()
            ->where('test_id', $readingTest->id)
            ->firstOrFail();

        $section = TestSection::query()->findOrFail($pivot->test_section_id);
        $module = $this->builder->readingModule($readingTest);
        $bank = $this->builder->questionBank($readingTest);

        $this->builder->saveQuestion(
            $readingTest,
            $module,
            $section,
            $bank,
            $request->validated(),
            $question
        );

        return back()->with('status', 'Question updated successfully.');
    }

    public function destroyQuestion(ExamTest $readingTest, TestSection $section, Question $question): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('update', $readingTest);
        $this->ensureSectionBelongsToTest($readingTest, $section);

        $linked = $question->testQuestions()
            ->where('test_id', $readingTest->id)
            ->where('test_section_id', $section->id)
            ->exists();

        abort_unless($linked, 404);

        $this->builder->deleteQuestion($section, $question);

        return back()->with('status', 'Question removed successfully.');
    }

    public function exportJson(ExamTest $readingTest): StreamedResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('view', $readingTest);

        $payload = $this->builder->exportTest($readingTest);
        $filename = $readingTest->slug.'-reading-test.json';

        return Response::streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function importJson(ImportReadingTestRequest $request, ExamTest $readingTest): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('update', $readingTest);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $payload = json_decode($file->get(), true);

        if (! is_array($payload)) {
            return back()->withErrors(['file' => 'Invalid JSON file.']);
        }

        $payload['test'] = array_merge($payload['test'] ?? [], [
            'slug' => $readingTest->slug,
            'title' => $readingTest->title,
        ]);

        $this->builder->clearTestContent($readingTest);

        foreach ($payload['passages'] ?? [] as $passageData) {
            $module = $this->builder->readingModule($readingTest);
            $section = $this->builder->savePassage($module, $passageData);
            $bank = $this->builder->questionBank($readingTest);

            foreach ($passageData['questions'] ?? [] as $questionData) {
                $this->builder->saveQuestion($readingTest, $module, $section, $bank, [
                    ...$questionData,
                    'options' => collect($questionData['options'] ?? [])->map(
                        fn (array $option): string => $option['option_text'] ?? $option['label'] ?? ''
                    )->filter()->values()->all(),
                ]);
            }
        }

        return redirect()
            ->route('admin.reading-tests.builder', $readingTest)
            ->with('status', 'Test content imported successfully.');
    }

    private function ensureReadingTest(ExamTest $test): void
    {
        if ($test->type !== TestType::ReadingTest) {
            abort(404);
        }
    }

    private function ensureSectionBelongsToTest(ExamTest $test, TestSection $section): void
    {
        $module = $this->builder->readingModule($test);

        abort_unless((int) $section->test_module_id === (int) $module->id, 404);
    }
}
