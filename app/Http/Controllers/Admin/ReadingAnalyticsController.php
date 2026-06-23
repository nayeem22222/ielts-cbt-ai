<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamTest;
use App\Models\ReadingAnalytics;
use App\Services\Exam\Analytics\ReadingAnalyticsReportService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingAnalyticsController extends Controller
{
    public function __construct(private readonly ReadingAnalyticsReportService $reports)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', ExamTest::class);

        return view('pages.admin.reading-analytics.index', [
            'overview' => $this->reports->overview(),
        ]);
    }

    public function show(ExamTest $readingTest): View
    {
        abort_unless($readingTest->type->value === 'reading_test', 404);
        $this->authorize('view', $readingTest);

        return view('pages.admin.reading-analytics.show', [
            'test' => $readingTest,
            'summary' => $this->reports->testSummary($readingTest),
        ]);
    }

    public function attempt(ReadingAnalytics $readingAnalytic): View
    {
        $this->authorize('view', $readingAnalytic->test);

        $readingAnalytic->load(['user', 'attempt', 'result.statistics']);

        return view('pages.admin.reading-analytics.attempt', [
            'analytics' => $readingAnalytic,
        ]);
    }

    public function export(ExamTest $readingTest): StreamedResponse
    {
        abort_unless($readingTest->type->value === 'reading_test', 404);
        $this->authorize('view', $readingTest);

        return $this->reports->exportTestReport($readingTest);
    }
}
