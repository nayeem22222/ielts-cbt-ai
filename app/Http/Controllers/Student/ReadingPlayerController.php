<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AutosaveReadingAttemptRequest;
use App\Models\TestAttempt;
use App\Services\Exam\ReadingPlayerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingPlayerController extends Controller
{
    public function __construct(private readonly ReadingPlayerService $player)
    {
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

    private function formatTime(?int $seconds): string
    {
        $seconds = max(0, (int) ($seconds ?? 3600));

        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
