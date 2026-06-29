<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Listening;

use App\Actions\Listening\Student\AutoSubmitExpiredListeningAttemptAction;
use App\Actions\Listening\Student\BuildListeningPlayerPayloadAction;
use App\Actions\Listening\Student\FlagListeningQuestionAction;
use App\Actions\Listening\Student\StartListeningAttemptAction;
use App\Actions\Listening\Student\SubmitListeningAttemptAction;
use App\Actions\Listening\Student\TransitionToTransferPhaseAction;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Listening\FlagListeningQuestionRequest;
use App\Http\Requests\Student\Listening\StartListeningAttemptRequest;
use App\Http\Requests\Student\Listening\SubmitListeningAttemptRequest;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\Student\ListeningAttemptService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use App\Services\Listening\Student\ListeningTestAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListeningAttemptController extends Controller
{
    public function __construct(
        private readonly ListeningTestAccessService $access,
        private readonly StartListeningAttemptAction $startAttempt,
        private readonly BuildListeningPlayerPayloadAction $buildPayload,
        private readonly SubmitListeningAttemptAction $submitAttempt,
        private readonly FlagListeningQuestionAction $flagQuestion,
        private readonly ListeningAttemptService $attempts,
        private readonly ListeningOfficialTimerService $officialTimer,
        private readonly AutoSubmitExpiredListeningAttemptAction $autoSubmit,
        private readonly TransitionToTransferPhaseAction $enterTransfer,
    ) {}

    public function start(StartListeningAttemptRequest $request, ListeningTest $listeningTest): View|RedirectResponse
    {
        if (! $this->access->isStartable($listeningTest)) {
            return redirect()
                ->route('student.listening.tests.instructions', $listeningTest)
                ->with('error', 'This listening test is not available.');
        }

        $attempt = $this->startAttempt->execute($request->user(), $listeningTest, [
            'ip_address' => $request->ip(),
            'browser_info' => ['user_agent' => $request->userAgent()],
        ]);

        if ($attempt->status !== ListeningAttemptStatus::InProgress) {
            return redirect()->route('student.listening.attempts.submitted', $attempt);
        }

        if (config('listening.official_flow.auto_enter_transfer_phase', true) && $this->officialTimer->shouldEnterTransfer($attempt)) {
            $this->enterTransfer->execute($attempt);
            $attempt = $attempt->refresh();
        }

        if ($this->officialTimer->isExpired($attempt) && config('listening.official_flow.auto_submit_on_expiry', true)) {
            $this->autoSubmit->execute($attempt);
            $attempt = $attempt->refresh();

            return redirect()->route('student.listening.attempts.expired', $attempt);
        }

        $this->officialTimer->sync($attempt, []);

        $payload = $this->buildPayload->execute($attempt);

        return view('student.listening.player.show', [
            'attempt' => $attempt->load('test'),
            'payload' => $payload->toArray(),
        ]);
    }

    public function legacyPlayer(ListeningAttempt $attempt): RedirectResponse
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());
        $attempt->loadMissing('test');

        abort_unless($attempt->test !== null, 404);

        return redirect()->route('student.listening.tests.start', $attempt->test);
    }

    public function submit(SubmitListeningAttemptRequest $request, ListeningAttempt $attempt): RedirectResponse
    {
        $this->submitAttempt->execute($attempt, auto: false);

        return redirect()->route('student.listening.attempts.submitted', $attempt);
    }

    public function submitted(ListeningAttempt $attempt): View
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());

        return view('student.listening.player.submitted', [
            'attempt' => $attempt->load('test'),
        ]);
    }

    public function expired(ListeningAttempt $attempt): View
    {
        $this->attempts->assertOwnedBy($attempt, auth()->user());

        return view('student.listening.player.expired', [
            'attempt' => $attempt->load('test'),
        ]);
    }

    public function flag(FlagListeningQuestionRequest $request, ListeningAttempt $attempt, ListeningQuestion $question)
    {
        $this->flagQuestion->execute($attempt, $question, (bool) $request->boolean('flagged'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'flagged' => $request->boolean('flagged')]);
        }

        return back();
    }
}
