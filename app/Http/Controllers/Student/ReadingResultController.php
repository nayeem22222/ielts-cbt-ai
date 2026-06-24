<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ReadingAttempt;
use App\Services\Exam\ReadingEvaluationService;
use App\Services\Exam\ReadingResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingResultController extends Controller
{
    public function __construct(
        private readonly ReadingResultService $results,
        private readonly ReadingEvaluationService $evaluation,
    ) {
    }

    public function show(ReadingAttempt $attempt): View|RedirectResponse
    {
        $this->evaluation->assertCanViewResult($attempt);

        if ($attempt->status?->value === 'in_progress') {
            return redirect()->route('reading-tests.start', $attempt->test);
        }

        $data = $this->results->buildResultPageData($attempt);

        return view('pages.reading-tests.result', $data);
    }

    public function review(ReadingAttempt $attempt): View|RedirectResponse
    {
        $this->evaluation->assertCanViewResult($attempt);

        if ($attempt->status?->value === 'in_progress') {
            return redirect()->route('reading-tests.start', $attempt->test);
        }

        $data = $this->results->buildReviewPageData($attempt);

        return view('pages.reading-tests.result-review', $data);
    }
}
