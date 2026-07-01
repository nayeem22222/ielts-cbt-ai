<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Listening\ListeningResultController;
use App\Http\Controllers\Admin\Listening\ListeningReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:listening.results.admin_view')->prefix('listening/results')->name('listening.results.')->group(function (): void {
    Route::get('/', [ListeningResultController::class, 'index'])->name('index');
    Route::get('/{result}', [ListeningResultController::class, 'show'])->name('show');

    Route::post('/{result}/publish', [ListeningResultController::class, 'publish'])
        ->middleware('permission:listening.results.publish')
        ->name('publish');

    Route::post('/{result}/hide', [ListeningResultController::class, 'hide'])
        ->middleware('permission:listening.results.hide')
        ->name('hide');

    Route::post('/{result}/rebuild', [ListeningResultController::class, 'rebuild'])
        ->middleware('permission:listening.results.rebuild')
        ->name('rebuild');

    Route::middleware('permission:listening.review.admin_view')->group(function (): void {
        Route::get('/{result}/review', [ListeningReviewController::class, 'show'])->name('review.show');
        Route::get('/{result}/review/questions/{questionNumber}', [ListeningReviewController::class, 'question'])
            ->whereNumber('questionNumber')
            ->name('review.question');
        Route::post('/{result}/review/rebuild', [ListeningReviewController::class, 'rebuild'])
            ->middleware('permission:listening.review.rebuild')
            ->name('review.rebuild');
    });
});
