<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Enums\Course\PublishStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\ReadingAttempt;
use App\Models\ReadingTest;
use App\Services\Exam\ReadingAnswerService;
use App\Services\Exam\ReadingTestRendererService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReadingTestRendererController extends Controller
{
    public function __construct(
        private readonly ReadingTestRendererService $renderer,
        private readonly ReadingAnswerService $answers,
    ) {
    }

    public function index(): View
    {
        $tests = $this->renderer->publishedTests();

        if ($tests->isEmpty()) {
            return view('pages.reading-tests.empty');
        }

        return view('pages.reading-tests.index', [
            'tests' => $tests,
        ]);
    }

    public function show(ReadingTest $readingTest): View|RedirectResponse
    {
        $test = $this->assertPublished($readingTest);
        $test = $this->renderer->loadForRenderer($test);
        $user = $this->authenticatedUser();

        $attempts = ReadingAttempt::query()
            ->where('user_id', $user->id)
            ->where('reading_test_id', $test->id)
            ->orderByDesc('id')
            ->get();

        $inProgressAttempt = $attempts->first(
            fn (ReadingAttempt $attempt): bool => $attempt->status === TestAttemptStatus::InProgress,
        );

        return view('pages.reading-tests.show', [
            'test' => $test,
            'attempts' => $attempts,
            'inProgressAttempt' => $inProgressAttempt,
            'latestFinishedAttempt' => $attempts->first(
                fn (ReadingAttempt $attempt): bool => in_array($attempt->status, [TestAttemptStatus::Submitted, TestAttemptStatus::Completed], true),
            ),
        ]);
    }

    public function start(ReadingTest $readingTest): View|RedirectResponse
    {
        $test = $this->assertPublished($readingTest);
        $test = $this->renderer->loadForRenderer($test);
        $user = $this->authenticatedUser();

        $attempt = $this->answers->getOrCreateAttempt($user, $test);

        $rendererState = array_merge(
            $this->renderer->buildRendererState($test),
            $this->answers->buildAttemptPayload($attempt, $test),
        );

        return view('pages.reading-tests.start', [
            'test' => $test,
            'attempt' => $attempt,
            'rendererState' => $rendererState,
            'renderer' => $this->renderer,
        ]);
    }

    private function authenticatedUser(): \App\Models\User
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        return $user;
    }

    private function assertPublished(ReadingTest $readingTest): ReadingTest
    {
        abort_unless($readingTest->status === PublishStatus::Published, 404);

        return $readingTest;
    }
}
