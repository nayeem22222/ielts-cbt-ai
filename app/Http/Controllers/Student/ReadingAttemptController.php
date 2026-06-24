<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MarkReadingVisitedRequest;
use App\Http\Requests\Student\SaveReadingPositionRequest;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;
use App\Services\Exam\ReadingAnswerService;
use App\Services\Exam\ReadingSubmitService;
use Illuminate\Http\JsonResponse;

class ReadingAttemptController extends Controller
{
    public function __construct(
        private readonly ReadingAnswerService $answers,
        private readonly ReadingSubmitService $submit,
    ) {
    }

    public function savePosition(SaveReadingPositionRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $validated = $request->validated();

        $payload = $this->answers->savePosition(
            $attempt,
            (int) $validated['current_passage'],
            (int) $validated['current_question'],
        );

        return response()->json(['data' => $payload]);
    }

    public function markVisited(MarkReadingVisitedRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $question = ReadingQuestion::query()->findOrFail((int) $request->validated('question_id'));
        $visited = $this->submit->markVisited($attempt, $question);

        return response()->json([
            'data' => [
                'success' => true,
                'visited_questions' => $visited,
            ],
        ]);
    }
}
