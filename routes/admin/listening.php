<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Listening\ListeningTestController;
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
});
