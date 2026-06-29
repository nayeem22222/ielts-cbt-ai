<?php

declare(strict_types=1);

use App\Http\Controllers\Student\Listening\ListeningAnswerController;
use App\Http\Controllers\Student\Listening\ListeningAttemptController;
use App\Http\Controllers\Student\Listening\ListeningAudioStreamController;
use App\Http\Controllers\Student\Listening\ListeningAutoSaveController;
use App\Http\Controllers\Student\Listening\ListeningNavigationController;
use App\Http\Controllers\Student\Listening\ListeningOfficialFlowController;
use App\Http\Controllers\Student\Listening\ListeningTestPlayerController;
use App\Http\Controllers\Student\Listening\ListeningTimerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['module:listening'])->group(function (): void {
    Route::get('/tests', [ListeningTestPlayerController::class, 'index'])
        ->name('tests.index');

    Route::get('/tests/{listeningTest:slug}/instructions', [ListeningTestPlayerController::class, 'instructions'])
        ->name('tests.instructions');

    Route::post('/tests/{listeningTest:slug}/start', [ListeningAttemptController::class, 'start'])
        ->name('tests.start');

    Route::middleware('listening.attempt.active')->group(function (): void {
        Route::get('/attempts/{attempt}/player', [ListeningAttemptController::class, 'player'])
            ->name('attempts.player');

        Route::post('/attempts/{attempt}/answers/save', [ListeningAnswerController::class, 'save'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.answers.save');

        Route::post('/attempts/{attempt}/answers/bulk-save', [ListeningAnswerController::class, 'bulkSave'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.answers.bulk_save');

        Route::post('/attempts/{attempt}/autosave', [ListeningAutoSaveController::class, 'save'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.autosave');

        Route::post('/attempts/{attempt}/autosave/bulk', [ListeningAutoSaveController::class, 'bulk'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.autosave.bulk');

        Route::post('/attempts/{attempt}/navigation/update', [ListeningNavigationController::class, 'update'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.navigation.update');

        Route::post('/attempts/{attempt}/state/sync', [ListeningNavigationController::class, 'sync'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.state.sync');

        Route::post('/attempts/{attempt}/questions/{question}/review', [ListeningNavigationController::class, 'markReview'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.questions.review');

        Route::post('/attempts/{attempt}/questions/{question}/flag', [ListeningAttemptController::class, 'flag'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.questions.flag');

        Route::post('/attempts/{attempt}/submit', [ListeningAttemptController::class, 'submit'])
            ->middleware('throttle:listening-submit')
            ->name('attempts.submit');

        Route::get('/attempts/{attempt}/audio/{section}', [ListeningAudioStreamController::class, 'section'])
            ->whereNumber('section')
            ->name('attempts.audio.section');

        Route::get('/attempts/{attempt}/timer/state', [ListeningTimerController::class, 'state'])
            ->middleware('throttle:listening-timer')
            ->name('attempts.timer.state');

        Route::post('/attempts/{attempt}/timer/sync', [ListeningTimerController::class, 'sync'])
            ->middleware('throttle:listening-timer')
            ->name('attempts.timer.sync');

        Route::post('/attempts/{attempt}/audio/start', [ListeningOfficialFlowController::class, 'startAudio'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.audio.start');

        Route::post('/attempts/{attempt}/audio/end', [ListeningOfficialFlowController::class, 'endAudio'])
            ->middleware('throttle:listening-autosave')
            ->name('attempts.audio.end');

        Route::post('/attempts/{attempt}/phase/transfer', [ListeningOfficialFlowController::class, 'enterTransfer'])
            ->middleware('throttle:listening-timer')
            ->name('attempts.phase.transfer');
    });

    Route::post('/attempts/{attempt}/auto-submit', [ListeningTimerController::class, 'autoSubmit'])
        ->middleware('throttle:listening-submit')
        ->name('attempts.auto_submit');

    Route::get('/attempts/{attempt}/submitted', [ListeningAttemptController::class, 'submitted'])
        ->name('attempts.submitted');

    Route::get('/attempts/{attempt}/expired', [ListeningAttemptController::class, 'expired'])
        ->name('attempts.expired');

    Route::get('/attempts/{attempt}/groups/{group}/image', [ListeningAudioStreamController::class, 'groupImage'])
        ->name('attempts.groups.image');
});
