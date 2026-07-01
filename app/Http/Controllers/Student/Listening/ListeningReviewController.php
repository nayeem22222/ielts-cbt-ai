<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningResult;
use App\Policies\Listening\ListeningReviewPolicy;
use App\Services\Listening\Review\ListeningAudioReviewService;
use App\Services\Listening\Review\ListeningReviewAccessService;
use App\Services\Listening\Review\ListeningReviewService;
use App\Services\Listening\Student\ListeningAudioAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListeningReviewController extends Controller
{
    public function __construct(
        private readonly ListeningReviewService $reviews,
        private readonly ListeningReviewAccessService $access,
        private readonly ListeningAudioReviewService $audioReview,
        private readonly ListeningAudioAccessService $audioAccess,
        private readonly ListeningReviewPolicy $policy,
    ) {}

    public function show(Request $request, ListeningResult $result): View
    {
        abort_unless($this->policy->viewStudent($request->user(), $result), 403);

        $data = $this->reviews->getReviewForStudent($request->user(), $result);

        return view('student.listening.review.show', $data);
    }

    public function question(Request $request, ListeningResult $result, int $questionNumber): View
    {
        abort_unless($this->policy->viewStudent($request->user(), $result), 403);

        $data = $this->reviews->getQuestionReviewForStudent($request->user(), $result, $questionNumber);

        return view('student.listening.review.question', $data);
    }

    public function audio(Request $request, ListeningResult $result, int $section): StreamedResponse
    {
        if (config('listening.audio_access.use_signed_routes', true) && ! $request->hasValidSignature()) {
            abort(403);
        }

        abort_unless($this->policy->viewAudioReview($request->user(), $result), 403);

        $attempt = $result->attempt;

        abort_if($attempt === null || (int) $attempt->user_id !== (int) $request->user()->id, 403);

        return $this->audioAccess->streamSectionAudio($attempt, $section);
    }
}
