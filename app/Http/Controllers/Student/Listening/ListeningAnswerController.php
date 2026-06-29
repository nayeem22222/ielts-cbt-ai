<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\BulkSaveListeningAnswersAction;
use App\Actions\Listening\Student\SaveListeningAnswerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\BulkSaveListeningAnswersRequest;
use App\Http\Requests\Student\Listening\SaveListeningAnswerRequest;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Student\ListeningAttemptService;
use Illuminate\Http\JsonResponse;

class ListeningAnswerController extends Controller
{
    public function __construct(
        private readonly SaveListeningAnswerAction $saveAnswer,
        private readonly BulkSaveListeningAnswersAction $bulkSave,
        private readonly ListeningAttemptService $attempts,
    ) {}

    public function save(SaveListeningAnswerRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $question = ListeningQuestion::query()->find((int) $request->integer('question_id'));

        if ($question === null) {
            return response()->json(['message' => 'Question not found.'], 422);
        }

        try {
            $row = $this->saveAnswer->execute($attempt, $question, $request->input('student_answer'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        if ($request->filled('current_section_number') && $request->filled('current_question_number')) {
            $this->attempts->updatePosition(
                $attempt,
                (int) $request->integer('current_section_number'),
                (int) $request->integer('current_question_number'),
            );
        }

        return response()->json([
            'success' => true,
            'question_id' => $question->id,
            'answer_status' => $row->answer_status?->value,
            'total_answered' => $attempt->refresh()->total_answered,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function bulkSave(BulkSaveListeningAnswersRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $saved = $this->bulkSave->execute($attempt, $request->input('answers', []));

        if ($request->filled('current_section_number') && $request->filled('current_question_number')) {
            $this->attempts->updatePosition(
                $attempt,
                (int) $request->integer('current_section_number'),
                (int) $request->integer('current_question_number'),
            );
        }

        return response()->json([
            'success' => true,
            'saved_count' => count($saved),
            'total_answered' => $attempt->refresh()->total_answered,
            'saved_at' => now()->toIso8601String(),
        ]);
    }
}
