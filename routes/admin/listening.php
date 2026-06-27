<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Listening\ListeningSectionController;
use App\Http\Controllers\Admin\Listening\ListeningSectionTranscriptController;
use App\Http\Controllers\Admin\Listening\ListeningTestController;
use App\Http\Controllers\Admin\Listening\ListeningTranscriptController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:listening.tests.view')->prefix('listening/tests')->name('listening.tests.')->group(function (): void {
    Route::get('/', [ListeningTestController::class, 'index'])->name('index');
    Route::get('/create', [ListeningTestController::class, 'create'])->name('create');
    Route::post('/', [ListeningTestController::class, 'store'])->name('store');
    Route::get('/{listeningTest}', [ListeningTestController::class, 'show'])->name('show');
    Route::get('/{listeningTest}/edit', [ListeningTestController::class, 'edit'])->name('edit');
    Route::put('/{listeningTest}', [ListeningTestController::class, 'update'])->name('update');
    Route::delete('/{listeningTest}', [ListeningTestController::class, 'destroy'])->name('destroy');

    Route::post('/{listeningTest}/publish', [ListeningTestController::class, 'publish'])->name('publish');
    Route::post('/{listeningTest}/unpublish', [ListeningTestController::class, 'unpublish'])->name('unpublish');
    Route::post('/{listeningTest}/archive', [ListeningTestController::class, 'archive'])->name('archive');
    Route::post('/{id}/restore', [ListeningTestController::class, 'restore'])->name('restore')->whereNumber('id');
    Route::post('/{listeningTest}/duplicate', [ListeningTestController::class, 'duplicate'])->name('duplicate');
    Route::put('/{listeningTest}/settings', [ListeningTestController::class, 'updateSettings'])->name('settings.update');

    Route::middleware('permission:listening.sections.view,listening.tests.view,listening.tests.update')->prefix('{listeningTest}/sections')->name('sections.')->group(function (): void {
        Route::get('/', [ListeningSectionController::class, 'index'])->name('index');
        Route::get('/create', [ListeningSectionController::class, 'create'])->name('create');
        Route::post('/', [ListeningSectionController::class, 'store'])->name('store');
        Route::post('/default', [ListeningSectionController::class, 'createDefaultSections'])->name('default');
        Route::post('/reorder', [ListeningSectionController::class, 'reorder'])->name('reorder');
        Route::get('/{section}', [ListeningSectionController::class, 'show'])->name('show');
        Route::get('/{section}/edit', [ListeningSectionController::class, 'edit'])->name('edit');
        Route::put('/{section}', [ListeningSectionController::class, 'update'])->name('update');
        Route::delete('/{section}', [ListeningSectionController::class, 'destroy'])->name('destroy');
        Route::post('/{sectionId}/restore', [ListeningSectionController::class, 'restore'])->name('restore')->whereNumber('sectionId');

        Route::post('/{section}/transcript/attach', [ListeningSectionTranscriptController::class, 'attach'])->name('transcript.attach');
        Route::delete('/{section}/transcript/detach', [ListeningSectionTranscriptController::class, 'detach'])->name('transcript.detach');
    });
});

Route::middleware('permission:listening.transcripts.view,listening.tests.view')->prefix('listening/transcripts')->name('listening.transcripts.')->group(function (): void {
    Route::get('/', [ListeningTranscriptController::class, 'index'])->name('index');
    Route::get('/create', [ListeningTranscriptController::class, 'create'])->name('create');
    Route::post('/', [ListeningTranscriptController::class, 'store'])->name('store');
    Route::get('/{transcript}', [ListeningTranscriptController::class, 'show'])->name('show');
    Route::get('/{transcript}/edit', [ListeningTranscriptController::class, 'edit'])->name('edit');
    Route::put('/{transcript}', [ListeningTranscriptController::class, 'update'])->name('update');
    Route::delete('/{transcript}', [ListeningTranscriptController::class, 'destroy'])->name('destroy');
    Route::put('/{transcript}/timestamps', [ListeningTranscriptController::class, 'updateTimestamps'])->name('timestamps.update');
});
