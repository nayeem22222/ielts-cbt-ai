<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Listening\Student\AutoSubmitExpiredListeningAttemptAction;
use App\Actions\Listening\Student\TransitionToTransferPhaseAction;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureListeningAttemptIsActive
{
    public function __construct(
        private readonly ListeningAttemptService $attempts,
        private readonly ListeningOfficialTimerService $timer,
        private readonly AutoSubmitExpiredListeningAttemptAction $autoSubmit,
        private readonly TransitionToTransferPhaseAction $enterTransfer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ListeningAttempt|null $attempt */
        $attempt = $request->route('attempt');

        if (! $attempt instanceof ListeningAttempt) {
            abort(404);
        }

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $this->attempts->assertOwnedBy($attempt, $user);

        if ($attempt->status !== ListeningAttemptStatus::InProgress) {
            if ($request->expectsJson()) {
                abort(403, 'Attempt is not active.');
            }

            return redirect()->route('student.listening.attempts.result', $attempt);
        }

        if ($this->timer->shouldEnterTransfer($attempt) && config('listening.official_flow.auto_enter_transfer_phase', true)) {
            $this->enterTransfer->execute($attempt);
            $attempt = $attempt->refresh();
        }

        if ($this->timer->isExpired($attempt) && config('listening.official_flow.auto_submit_on_expiry', true)) {
            $this->autoSubmit->execute($attempt);
            $attempt = $attempt->refresh();

            if ($request->expectsJson()) {
                return response()->json([
                    'expired' => true,
                    'redirect' => route('student.listening.attempts.expired', $attempt),
                ], 403);
            }

            return redirect()->route('student.listening.attempts.expired', $attempt);
        }

        $this->timer->sync($attempt, []);

        return $next($request);
    }
}
