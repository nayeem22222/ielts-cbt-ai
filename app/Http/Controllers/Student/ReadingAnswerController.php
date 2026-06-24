<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveReadingAnswerRequest;
use App\Http\Requests\Student\ToggleReadingFlagRequest;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;
use App\Services\Exam\ReadingAnswerService;
use Illuminate\Http\JsonResponse;

class ReadingAnswerController extends Controller
{
    public function __construct(private readonly ReadingAnswerService $answers)
    {
    }

    public function store(SaveReadingAnswerRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $payload = $this->answers->saveAnswer($attempt, $request->validated());

        return response()->json(['data' => $payload]);
    }

    public function toggleFlag(
        ToggleReadingFlagRequest $request,
        ReadingAttempt $attempt,
        ReadingQuestion $question,
    ): JsonResponse {
        $payload = $this->answers->toggleFlag(
            $attempt,
            $question,
            (bool) $request->validated('flagged'),
        );

        return response()->json(['data' => $payload]);
    }
}
