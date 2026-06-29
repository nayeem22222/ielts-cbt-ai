<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\RecoverListeningDraftAnswersAction;
use App\Actions\Listening\Student\SyncListeningPlayerStateAction;
use App\Actions\Listening\Student\ToggleListeningQuestionFlagAction;
use App\Actions\Listening\Student\UpdateListeningCurrentPositionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\MarkListeningQuestionReviewRequest;
use App\Http\Requests\Student\Listening\SyncListeningPlayerStateRequest;
use App\Http\Requests\Student\Listening\UpdateListeningNavigationRequest;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Student\ListeningAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ListeningNavigationController extends Controller
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly UpdateListeningCurrentPositionAction $updatePosition,
        private readonly ToggleListeningQuestionFlagAction $toggleFlag,
        private readonly SyncListeningPlayerStateAction $syncState,
        private readonly RecoverListeningDraftAnswersAction $recoverDraft,
    ) {}

    public function update(UpdateListeningNavigationRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        try {
            $state = $this->updatePosition->execute(
                $attempt,
                (int) $request->integer('current_section_number'),
                (int) $request->integer('current_question_number'),
                $request->input('direction'),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid navigation.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'navigation' => $state->toArray(),
        ]);
    }

    public function markReview(
        MarkListeningQuestionReviewRequest $request,
        ListeningAttempt $attempt,
        ListeningQuestion $question,
    ): JsonResponse {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        try {
            $result = $this->toggleFlag->execute($attempt, $question, (bool) $request->boolean('flagged'));
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid question.', 'errors' => $e->errors()], 422);
        }

        return response()->json($result);
    }

    public function sync(SyncListeningPlayerStateRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        if ($request->filled('recover_answers') && is_array($request->input('recover_answers'))) {
            $result = $this->recoverDraft->execute($attempt, $request->input('recover_answers'));

            return response()->json($result);
        }

        try {
            $result = $this->syncState->execute($attempt, $request->validated());
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid state.', 'errors' => $e->errors()], 422);
        }

        return response()->json($result);
    }
}
