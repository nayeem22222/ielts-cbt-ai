<?php

declare(strict_types=1);

use App\Http\Controllers\Student\Listening\ListeningAttemptController;
use App\Http\Controllers\Student\Listening\ListeningTestPlayerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListeningTestPlayerController::class, 'index'])
    ->name('tests.index');

Route::get('/{listeningTest:slug}/instructions', [ListeningTestPlayerController::class, 'instructions'])
    ->name('tests.instructions');

Route::match(['get', 'post'], '/{listeningTest:slug}/start', [ListeningAttemptController::class, 'start'])
    ->name('tests.start');
