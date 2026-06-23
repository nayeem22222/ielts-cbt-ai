<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AutosaveReadingAttemptRequest;
use App\Http\Requests\Student\SubmitReadingAttemptRequest;
use App\Models\Result;
use App\Models\TestAttempt;
use App\Services\Exam\ReadingPlayerService;
use App\Services\Exam\Scoring\ReadingScoringEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingPlayerController extends Controller
{
    public function __construct(
        private readonly ReadingPlayerService $player,
        private readonly ReadingScoringEngine $scoring,
    ) {
    }

    public function show(Request $request): View
    {
        $test = $this->player->resolvePublishedTest();

        if ($test === null) {
            return view('pages.exams.reading-empty');
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
