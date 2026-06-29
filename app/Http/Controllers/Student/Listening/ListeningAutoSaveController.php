<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\AutoSaveListeningAnswerAction;
use App\Actions\Listening\Student\AutoSaveListeningBulkAnswersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\AutoSaveListeningAnswerRequest;
use App\Http\Requests\Student\Listening\AutoSaveListeningBulkRequest;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Student\ListeningAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ListeningAutoSaveController extends Controller
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly AutoSaveListeningAnswerAction $autoSave,
        private readonly AutoSaveListeningBulkAnswersAction $bulkAutoSave,
    ) {}

    public function save(AutoSaveListeningAnswerRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        $question = ListeningQuestion::query()->find((int) $request->integer('question_id'));

        if ($question === null) {
            return response()->json(['message' => 'Question not found.'], 422);
        }

        try {
            $result = $this->autoSave->execute(
                $attempt,
                $question,
                $request->input('answer'),
                [
                    'client_answer_hash' => $request->input('client_answer_hash'),
                    'client_sequence' => $request->input('client_sequence'),
                    'client_saved_at' => $request->input('client_saved_at'),
                    'time_spent_seconds' => $request->input('time_spent_seconds'),
                    'saved_from' => 'single',
                ],
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json($result->toArray());
    }

    public function bulk(AutoSaveListeningBulkRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        try {
            $result = $this->bulkAutoSave->execute(
                $attempt,
                $request->input('answers', []),
                $request->filled('current_section_number') ? (int) $request->integer('current_section_number') : null,
                $request->filled('current_question_number') ? (int) $request->integer('current_question_number') : null,
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json($result);
    }
}
