<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreReadingHighlightRequest;
use App\Models\ReadingAttempt;
use App\Models\ReadingHighlight;
use App\Services\Exam\ReadingHighlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingHighlightController extends Controller
{
    public function __construct(private readonly ReadingHighlightService $highlights)
    {
    }

    public function index(Request $request, ReadingAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($attempt->user_id !== $user->id) {
            abort(403);
        }

        return response()->json([
            'data' => $this->highlights->listForAttempt($attempt, $user),
        ]);
    }

    public function store(StoreReadingHighlightRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $highlight = $this->highlights->store($attempt, $user, $request->validated());

        return response()->json(['data' => $highlight], 201);
    }

    public function destroy(Request $request, ReadingAttempt $attempt, ReadingHighlight $highlight): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $this->highlights->destroy($attempt, $user, $highlight);

        return response()->json(['success' => true]);
    }
}
