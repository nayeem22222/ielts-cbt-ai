<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningResult;
use App\Services\Listening\Result\ListeningResultAccessService;
use App\Services\Listening\Result\ListeningResultPageService;
use App\Services\Listening\Result\ListeningResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListeningResultController extends Controller
{
    public function __construct(
        private readonly ListeningResultService $results,
        private readonly ListeningResultAccessService $access,
        private readonly ListeningResultPageService $pages,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ListeningResult::class);

        $paginator = $this->results->getStudentResults(
            $request->user(),
            $request->only(['status']),
        );

        return view('student.listening.results.index', [
            'results' => $paginator,
        ]);
    }

    public function show(Request $request, ListeningResult $result): View|RedirectResponse
    {
        $this->authorize('view', $result);

        $result = $this->results->findForStudent($request->user(), (int) $result->id);

        abort_if($result === null, 404);

        $attempt = $result->attempt;

        abort_if($attempt === null, 404);

        return redirect()->route('student.listening.attempts.result', $attempt);
    }

    public function showByAttempt(Request $request, ListeningAttempt $attempt): View|RedirectResponse
    {
        $this->access->assertStudentCanViewAttempt(
            $request->user(),
            (int) $attempt->user_id,
            $attempt->status,
        );

        if ($attempt->status === ListeningAttemptStatus::InProgress) {
            $attempt->loadMissing('test');

            return redirect()->route('student.listening.tests.start', $attempt->test);
        }

        return $this->renderAttemptResultView($request, $attempt);
    }

    public function reviewByAttempt(Request $request, ListeningAttempt $attempt): View|RedirectResponse
    {
        $this->access->assertStudentCanViewAttempt(
            $request->user(),
            (int) $attempt->user_id,
            $attempt->status,
        );

        if ($attempt->status === ListeningAttemptStatus::InProgress) {
            $attempt->loadMissing('test');

            return redirect()->route('student.listening.tests.start', $attempt->test);
        }

        $result = $this->results->ensureResultExistsForAttempt($attempt);

        if ($result->status?->value === 'pending') {
            return view('student.listening.results.pending', [
                'attempt' => $attempt,
                'result' => $result,
            ]);
        }

        if ($result->status?->value === 'failed') {
            return view('student.listening.results.failed', [
                'attempt' => $attempt,
                'result' => $result,
            ]);
        }

        $this->authorize('view', $result);

        $data = $this->pages->buildReviewPageData($attempt, $request->user());

        return view('student.listening.results.result-review', $data);
    }

    private function renderAttemptResultView(Request $request, ListeningAttempt $attempt): View
    {
        $result = $this->results->ensureResultExistsForAttempt($attempt);

        if ($result->status?->value === 'pending') {
            return view('student.listening.results.pending', [
                'attempt' => $attempt,
                'result' => $result,
            ]);
        }

        if ($result->status?->value === 'failed') {
            return view('student.listening.results.failed', [
                'attempt' => $attempt,
                'result' => $result,
            ]);
        }

        $this->authorize('view', $result);

        $data = $this->pages->buildResultPageData($attempt);

        return view('student.listening.results.result', $data);
    }
}
