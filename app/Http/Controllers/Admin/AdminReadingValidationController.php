<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingTestValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminReadingValidationController extends Controller
{
    public function __construct(private readonly ReadingTestValidationService $validation)
    {
    }

    public function show(ReadingTest $readingTest): View
    {
        $this->authorize('update', $readingTest);

        $result = session('validation_result') ?? $this->validation->validatePublishReady($readingTest);

        return view('pages.admin.reading-tests.validation', [
            'test' => $readingTest,
            'result' => $result,
        ]);
    }

    public function validate(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('update', $readingTest);

        $result = $this->validation->validatePublishReady($readingTest);

        return redirect()
            ->route('admin.reading-tests.validation', $readingTest)
            ->with('validation_result', $result)
            ->with(
                $result['is_valid'] ? 'status' : 'error',
                $result['is_valid']
                    ? 'Validation passed. This reading test is ready to publish.'
                    : 'Validation found '.count($result['errors']).' error(s). Fix them before publishing.',
            );
    }

    public function previewFull(ReadingTest $readingTest, Request $request): View
    {
        $this->authorize('view', $readingTest);

        $test = $this->validation->loadTest($readingTest);

        return view('pages.admin.reading-tests.preview-full', [
            'test' => $test,
            'showCorrectAnswers' => $request->boolean('answers'),
            'showExplanations' => $request->boolean('explanations'),
            'answerRules' => \App\Enums\Exam\ReadingCompletionAnswerRule::cases(),
        ]);
    }
}
