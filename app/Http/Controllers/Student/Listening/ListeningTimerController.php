<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\AutoSubmitExpiredListeningAttemptAction;
use App\Actions\Listening\Student\SyncListeningTimerAction;
use App\Actions\Listening\Student\TransitionToTransferPhaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\SyncListeningTimerRequest;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningOfficialFlowService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use Illuminate\Http\JsonResponse;

class ListeningTimerController extends Controller
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly ListeningOfficialTimerService $timer,
        private readonly ListeningOfficialFlowService $flow,
        private readonly SyncListeningTimerAction $syncTimer,
        private readonly AutoSubmitExpiredListeningAttemptAction $autoSubmit,
        private readonly TransitionToTransferPhaseAction $enterTransfer,
    ) {}

    public function state(ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());

        $attempt = $this->processLifecycle($attempt);

        return response()->json([
            'success' => true,
            'timer' => $this->timer->getState($attempt)->toArray(),
            'phase' => $this->flow->getPhaseState($attempt)->toArray(),
        ]);
    }

    public function sync(SyncListeningTimerRequest $request, ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, $request->user());
        $this->attempts->assertEditable($attempt);

        $attempt = $this->processLifecycle($attempt);

        $state = $this->syncTimer->execute($attempt, $request->validated());

        return response()->json([
            'success' => true,
            'timer' => $state->toArray(),
            'phase' => $this->flow->getPhaseState($attempt->refresh())->toArray(),
        ]);
    }

    public function autoSubmit(ListeningAttempt $attempt): JsonResponse
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());

        $attempt = $attempt->refresh();

        if ($attempt->status !== \App\Enums\Listening\ListeningAttemptStatus::InProgress) {
            return response()->json([
                'success' => true,
                'already_submitted' => true,
                'redirect' => route('student.listening.attempts.submitted', $attempt),
                'timer' => $this->timer->getState($attempt)->toArray(),
            ]);
        }

        if ($this->timer->isExpired($attempt)) {
            $submitted = $this->autoSubmit->execute($attempt);

            return response()->json([
                'success' => true,
                'already_submitted' => false,
                'redirect' => route('student.listening.attempts.submitted', $submitted),
                'timer' => $this->timer->getState($submitted)->toArray(),
            ]);
        }

        return response()->json([
            'success' => true,
            'already_submitted' => false,
            'timer' => $this->timer->getState($attempt)->toArray(),
        ]);
    }

    private function processLifecycle(ListeningAttempt $attempt): ListeningAttempt
    {
        if ($this->timer->shouldEnterTransfer($attempt)) {
            $this->enterTransfer->execute($attempt);
            $attempt = $attempt->refresh();
        }

        if ($this->timer->shouldAutoSubmit($attempt)) {
            $this->autoSubmit->execute($attempt);
            $attempt = $attempt->refresh();
        }

        return $attempt;
    }
}
