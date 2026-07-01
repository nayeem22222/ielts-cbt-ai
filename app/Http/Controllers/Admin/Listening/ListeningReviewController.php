<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningResult;
use App\Policies\Listening\ListeningReviewPolicy;
use App\Services\Listening\Review\ListeningReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ListeningReviewController extends Controller
{
    public function __construct(
        private readonly ListeningReviewService $reviews,
        private readonly ListeningReviewPolicy $policy,
    ) {}

    public function show(ListeningResult $result): View
    {
        abort_unless($this->policy->viewAdmin(request()->user(), $result), 403);

        return view('admin.listening.review.show', $this->reviews->getReviewForAdmin($result));
    }

    public function question(ListeningResult $result, int $questionNumber): View
    {
        abort_unless($this->policy->viewAdmin(request()->user(), $result), 403);

        return view('admin.listening.review.question', $this->reviews->getQuestionReviewForAdmin($result, $questionNumber));
    }

    public function rebuild(ListeningResult $result): RedirectResponse
    {
        abort_unless($this->policy->rebuild(request()->user(), $result), 403);

        $this->reviews->rebuildReviewItems($result);

        return back()->with('status', 'Listening review items rebuilt.');
    }
}
