<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AutosaveReadingAttemptRequest;
use App\Http\Requests\Student\SubmitReadingAttemptRequest;
use App\Models\ExamTest;
use App\Models\ReadingTest;
use App\Models\Result;
use App\Models\TestAttempt;
use App\Services\Exam\ReadingAnswerService;
use App\Services\Exam\ReadingPlayerService;
use App\Services\Exam\ReadingTestRendererService;
use App\Services\Exam\Scoring\ReadingScoringEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReadingPlayerController extends Controller
{
    public function __construct(
        private readonly ReadingPlayerService $player,
        private readonly ReadingScoringEngine $scoring,
        private readonly ReadingTestRendererService $renderer,
        private readonly ReadingAnswerService $answers,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $legacyTests = $this->player->publishedTests();
        $volume5Tests = $this->renderer->publishedTests();

        if ($legacyTests->isEmpty() && $volume5Tests->isEmpty()) {
            return view('pages.exams.reading-empty');
        }

        if ($legacyTests->isEmpty()) {
            if ($volume5Tests->count() === 1 && ! $request->boolean('pick')) {
                return redirect()->route('exam.reading.show', $volume5Tests->first());
            }

            return redirect()->route('reading-tests.index');
        }

        if ($legacyTests->count() === 1 && $volume5Tests->isEmpty() && ! $request->boolean('pick')) {
            return redirect()->route('exam.reading.show', $legacyTests->first());
        }

        return view('pages.exams.reading-index', [
            'tests' => $legacyTests,
        ]);
    }

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $readingTest = ReadingTest::query()->published()->where('slug', $slug)->first();

        if ($readingTest !== null) {
            $user = $request->user();
            abort_unless($user !== null, 403);

            $test = $this->renderer->loadForRenderer($readingTest);
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

        $examTest = ExamTest::query()->where('slug', $slug)->firstOrFail();
        $test = $this->player->findPlayableTest($examTest);

        if ($test === null) {
            abort(404);
        }

        $attempt = $this->player->startOrResumeAttempt($request->user(), $test);
        $playerState = $this->player->buildPlayerState($attempt);

        return view('pages.exams.reading', [
            'test' => $test,
            'attempt' => $attempt,
            'playerState' => $playerState,
            'timer' => $this->formatTime($playerState['attempt']['time_remaining_seconds']),
        ]);
    }

    public function autosave(AutosaveReadingAttemptRequest $request, TestAttempt $attempt): JsonResponse
    {
        $result = $this->player->autosave($attempt, $request->validated());

        return response()->json(['data' => $result]);
    }

    public function submit(SubmitReadingAttemptRequest $request, TestAttempt $attempt): JsonResponse
    {
        if ($request->filled('answers') || $request->filled('question_timings')) {
            $this->player->autosave($attempt, $request->only([
                'current_section_id',
                'active_question_id',
                'time_remaining_seconds',
                'answers',
                'highlights',
                'notes',
                'question_timings',
            ]));
            $attempt->refresh();
        }

        $result = $this->scoring->scoreAttempt($attempt);

        return response()->json([
            'data' => [
                'result_uuid' => $result->uuid,
                'redirect_url' => route('exam.reading.results', $result),
                'overall_band' => (float) $result->overall_band,
                'raw_score' => (float) $result->raw_score,
                'max_score' => (float) $result->max_score,
            ],
        ]);
    }

    public function results(Request $request, Result $result): View
    {
        abort_unless($request->user()?->id === $result->attempt->user_id, 403);

        $result->load([
            'questionScores' => fn ($query) => $query->orderBy('question_number'),
            'statistics',
            'bandScores',
            'attempt.test',
            'attempt.readingAnalytics',
        ]);

        return view('pages.exams.reading-results', [
            'result' => $result,
        ]);
    }

    private function formatTime(?int $seconds): string
    {
        $seconds = max(0, (int) ($seconds ?? 3600));

        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
