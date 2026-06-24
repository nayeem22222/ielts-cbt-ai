<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ReadingAttempt;
use App\Services\Exam\ReadingSubmitService;
use App\Services\Exam\ReadingTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingSubmitController extends Controller
{
    public function __construct(
        private readonly ReadingSubmitService $submit,
        private readonly ReadingTimerService $timer,
    ) {
    }

    public function submit(Request $request, ReadingAttempt $attempt): JsonResponse
    {
        $this->timer->assertOwnedByUser($attempt);

        $payload = $this->submit->submit($attempt, false);

        return response()->json(['data' => $payload]);
    }

    public function autoSubmit(ReadingAttempt $attempt): JsonResponse
    {
        $this->timer->assertOwnedByUser($attempt);

        $payload = $this->submit->autoSubmit($attempt);

        return response()->json(['data' => $payload]);
    }

    public function submitted(ReadingAttempt $attempt): View|RedirectResponse
    {
        $this->timer->assertOwnedByUser($attempt);

        $attempt->loadMissing('test');

        if ($attempt->status?->value === 'in_progress') {
            return redirect()->route('reading-tests.start', $attempt->test);
        }

        if ($attempt->evaluated_at !== null || $attempt->status?->value === 'completed') {
            return redirect()->route('reading-attempts.result', $attempt);
        }

        return view('pages.reading-tests.submitted', [
            'attempt' => $attempt,
            'test' => $attempt->test,
        ]);
    }
}
