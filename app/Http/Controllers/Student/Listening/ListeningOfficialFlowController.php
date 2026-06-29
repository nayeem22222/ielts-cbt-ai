<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\MarkListeningAudioEndedAction;
use App\Actions\Listening\Student\MarkListeningAudioStartedAction;
use App\Actions\Listening\Student\TransitionToTransferPhaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\EndListeningAudioRequest;
use App\Http\Requests\Student\Listening\EnterListeningTransferPhaseRequest;
use App\Http\Requests\Student\Listening\StartListeningAudioRequest;
use App\Enums\Listening\ListeningAttemptPhase;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningAudioFlowService;
use App\Services\Listening\Student\ListeningOfficialFlowService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ListeningOfficialFlowController extends Controller
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly MarkListeningAudioStartedAction $audioStarted,
        private readonly MarkListeningAudioEndedAction $audioEnded,
        private readonly TransitionToTransferPhaseAction $enterTransfer,
        private readonly ListeningOfficialTimerService $timer,
        private readonly ListeningOfficialFlowService $flow,
        private readonly ListeningAudioFlowService $audioFlow,
    ) {}

    public function startAudio(StartListeningAudioRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        if (! $this->flow->canPlayAudio($attempt)) {
            return response()->json(['message' => 'Audio cannot be started in the current phase.'], 403);
        }

        try {
            $state = $this->audioStarted->execute($attempt, (int) $request->integer('section_number'));
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Audio start rejected.', 'errors' => $e->errors()], 422);
        }

        if ($request->filled('position')) {
            $this->audioFlow->updateAudioPosition($attempt, (int) $request->integer('section_number'), (float) $request->input('position'));
        }

        return response()->json([
            'success' => true,
            'audio' => $state->toArray(),
            'timer' => $this->timer->getState($attempt->refresh())->toArray(),
        ]);
    }

    public function endAudio(EndListeningAudioRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        try {
            $state = $this->audioEnded->execute($attempt, (int) $request->integer('section_number'));
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Audio end rejected.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'audio' => $state->toArray(),
        ]);
    }

    public function enterTransfer(EnterListeningTransferPhaseRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        if (! $this->timer->shouldEnterTransfer($attempt) && $attempt->current_phase !== ListeningAttemptPhase::Transfer) {
            return response()->json(['message' => 'Transfer phase is not available yet.'], 422);
        }

        if ($attempt->current_phase === ListeningAttemptPhase::Transfer) {
            return response()->json([
                'success' => true,
                'phase' => $this->flow->getPhaseState($attempt)->toArray(),
                'timer' => $this->timer->getState($attempt)->toArray(),
            ]);
        }

        try {
            $updated = $this->enterTransfer->execute($attempt);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid phase transition.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'phase' => $this->flow->getPhaseState($updated)->toArray(),
            'timer' => $this->timer->getState($updated)->toArray(),
        ]);
    }
}
