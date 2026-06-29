<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningReviewService;
use Illuminate\Http\JsonResponse;

class ListeningReviewController extends Controller
{
    public function __construct(
        private readonly ListeningReviewService $review,
        private readonly ListeningAttemptService $attempts,
    ) {}

    public function show(ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());

        return response()->json([
            'data' => $this->review->buildReviewSummary($attempt),
        ]);
    }
}
