<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\Student\ListeningTestAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListeningTestPlayerController extends Controller
{
    public function __construct(
        private readonly ListeningTestAccessService $access,
    ) {}

    public function index(Request $request): View
    {
        $tests = ListeningTest::query()
            ->published()
            ->active()
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->paginate(12);

        $testMeta = [];
        foreach ($tests as $test) {
            $testMeta[$test->id] = [
                'warnings' => $this->access->readinessWarnings($test),
                'startable' => $this->access->isStartable($test),
                'debug' => $this->access->debugVisibilityReasons($test, $request->user()),
            ];
        }

        return view('student.listening.tests.index', [
            'tests' => $tests,
            'testMeta' => $testMeta,
        ]);
    }

    public function instructions(Request $request, ListeningTest $listeningTest): View
    {
        if (! $this->access->isStartable($listeningTest)) {
            return view('student.listening.tests.unavailable', [
                'test' => $listeningTest,
                'reasons' => $this->access->startBlockingReasons($listeningTest),
                'debug' => $this->access->debugVisibilityReasons($listeningTest, $request->user()),
            ]);
        }

        $listeningTest->loadCount([
            'sections as active_sections_count' => fn ($query) => $query->where('is_active', true),
            'questions as active_questions_count' => fn ($query) => $query->where('is_active', true),
        ]);

        return view('student.listening.tests.instructions', [
            'test' => $listeningTest,
            'warnings' => $this->access->readinessWarnings($listeningTest),
            'debug' => $this->access->debugVisibilityReasons($listeningTest, $request->user()),
        ]);
    }
}
