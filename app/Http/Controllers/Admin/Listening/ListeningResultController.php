<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Listening\Result\ListeningResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListeningResultController extends Controller
{
    public function __construct(
        private readonly ListeningResultService $results,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAnyAdmin', ListeningResult::class);

        $filters = $request->only([
            'search',
            'user_id',
            'listening_test_id',
            'status',
            'band_score',
            'date_from',
            'date_to',
            'is_visible_to_student',
        ]);

        return view('admin.listening.results.index', [
            'results' => $this->results->getAdminResults($filters),
            'filters' => $filters,
            'tests' => ListeningTest::query()->orderBy('title')->get(['id', 'title']),
            'students' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function show(ListeningResult $result): View
    {
        $this->authorize('viewAdmin', $result);

        $result->load([
            'user',
            'test.setting',
            'attempt',
            'evaluation',
        ]);

        return view('admin.listening.results.show', [
            'result' => $result,
            'sectionBreakdown' => $result->section_breakdown ?? [],
            'questionSummary' => $result->question_summary ?? [],
            'failureReason' => $result->meta['failure_reason'] ?? null,
        ]);
    }

    public function publish(ListeningResult $result): RedirectResponse
    {
        $this->authorize('publish', $result);

        $this->results->publish($result);

        return back()->with('status', 'Listening result published to the student.');
    }

    public function hide(ListeningResult $result): RedirectResponse
    {
        $this->authorize('hide', $result);

        $this->results->hide($result);

        return back()->with('status', 'Listening result hidden from the student.');
    }

    public function rebuild(ListeningResult $result): RedirectResponse
    {
        $this->authorize('rebuild', $result);

        $this->results->rebuild($result);

        return back()->with('status', 'Listening result rebuilt from the latest evaluation.');
    }
}
