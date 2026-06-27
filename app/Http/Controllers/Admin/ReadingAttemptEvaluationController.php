<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingAttempt;
use App\Services\Exam\ReadingEvaluationService;
use Illuminate\Http\RedirectResponse;

class ReadingAttemptEvaluationController extends Controller
{
    public function __construct(private readonly ReadingEvaluationService $evaluation)
    {
    }

    public function reEvaluate(ReadingAttempt $attempt): RedirectResponse
    {
        $attempt->loadMissing('test');
        $this->authorize('update', $attempt->test);

        $this->evaluation->evaluateAttempt($attempt, force: true);

        return back()->with('status', 'Reading attempt re-evaluated successfully.');
    }
}
