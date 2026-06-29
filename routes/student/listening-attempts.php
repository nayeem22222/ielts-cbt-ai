<?php

declare(strict_types=1);

use App\Http\Controllers\Student\Listening\ListeningAnswerController;
use App\Http\Controllers\Student\Listening\ListeningAttemptController;
use App\Http\Controllers\Student\Listening\ListeningAudioStreamController;
use App\Http\Controllers\Student\Listening\ListeningAutoSaveController;
use App\Http\Controllers\Student\Listening\ListeningNavigationController;
use App\Http\Controllers\Student\Listening\ListeningOfficialFlowController;
use App\Http\Controllers\Student\Listening\ListeningReviewController;
use App\Http\Controllers\Student\Listening\ListeningTimerController;
use Illuminate\Support\Facades\Route;

Route::middleware('listening.attempt.active')->group(function (): void {
    Route::post('/{attempt}/answers/save', [ListeningAnswerController::class, 'save'])
        ->middleware('throttle:listening-autosave')
        ->name('answers.save');

    Route::post('/{attempt}/answers/bulk-save', [ListeningAnswerController::class, 'bulkSave'])
        ->middleware('throttle:listening-autosave')
        ->name('answers.bulk_save');

    Route::post('/{attempt}/autosave', [ListeningAutoSaveController::class, 'save'])
        ->middleware('throttle:listening-autosave')
        ->name('autosave');

    Route::post('/{attempt}/autosave/bulk', [ListeningAutoSaveController::class, 'bulk'])
        ->middleware('throttle:listening-autosave')
        ->name('autosave.bulk');

    Route::post('/{attempt}/navigation/update', [ListeningNavigationController::class, 'update'])
        ->middleware('throttle:listening-autosave')
        ->name('navigation.update');

    Route::post('/{attempt}/state/sync', [ListeningNavigationController::class, 'sync'])
        ->middleware('throttle:listening-autosave')
        ->name('state.sync');

    Route::post('/{attempt}/questions/{question}/review', [ListeningNavigationController::class, 'markReview'])
        ->middleware('throttle:listening-autosave')
        ->name('questions.review');

    Route::post('/{attempt}/questions/{question}/flag', [ListeningAttemptController::class, 'flag'])
        ->middleware('throttle:listening-autosave')
        ->name('questions.flag');

    Route::post('/{attempt}/submit', [ListeningAttemptController::class, 'submit'])
        ->middleware('throttle:listening-submit')
        ->name('submit');

    Route::get('/{attempt}/audio/{section}', [ListeningAudioStreamController::class, 'section'])
        ->whereNumber('section')
        ->name('audio.section');

    Route::get('/{attempt}/timer/state', [ListeningTimerController::class, 'state'])
        ->middleware('throttle:listening-timer')
        ->name('timer.state');

    Route::post('/{attempt}/timer/sync', [ListeningTimerController::class, 'sync'])
        ->middleware('throttle:listening-timer')
        ->name('timer.sync');

    Route::post('/{attempt}/audio/start', [ListeningOfficialFlowController::class, 'startAudio'])
        ->middleware('throttle:listening-autosave')
        ->name('audio.start');

    Route::post('/{attempt}/audio/end', [ListeningOfficialFlowController::class, 'endAudio'])
        ->middleware('throttle:listening-autosave')
        ->name('audio.end');

    Route::post('/{attempt}/phase/transfer', [ListeningOfficialFlowController::class, 'enterTransfer'])
        ->middleware('throttle:listening-timer')
        ->name('phase.transfer');
});

Route::get('/{attempt}/review', [ListeningReviewController::class, 'show'])
    ->middleware('listening.attempt.active')
    ->name('review');

Route::post('/{attempt}/auto-submit', [ListeningTimerController::class, 'autoSubmit'])
    ->middleware('throttle:listening-submit')
    ->name('auto_submit');

Route::get('/{attempt}/submitted', [ListeningAttemptController::class, 'submitted'])
    ->name('submitted');

Route::get('/{attempt}/expired', [ListeningAttemptController::class, 'expired'])
    ->name('expired');

Route::get('/{attempt}/groups/{group}/image', [ListeningAudioStreamController::class, 'groupImage'])
    ->name('groups.image');
