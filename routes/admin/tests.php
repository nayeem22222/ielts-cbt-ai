<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\ReadingAnalyticsController;
use App\Http\Controllers\Admin\ReadingTestBuilderController;
use App\Http\Controllers\Admin\ReadingTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:tests.view')->group(function (): void {
    Route::get('/reading-tests/trash', [ReadingTestController::class, 'trash'])->name('reading-tests.trash');
    Route::get('/reading-tests/export', [ReadingTestController::class, 'export'])->name('reading-tests.export');
    Route::post('/reading-tests/bulk', [ReadingTestController::class, 'bulk'])->name('reading-tests.bulk');
    Route::get('/reading-tests', [ReadingTestController::class, 'index'])->name('reading-tests.index');
    Route::get('/reading-tests/create', [ReadingTestController::class, 'create'])->name('reading-tests.create');
    Route::post('/reading-tests', [ReadingTestController::class, 'store'])->name('reading-tests.store');
    Route::get('/reading-tests/{reading_test}/edit', [ReadingTestController::class, 'edit'])->name('reading-tests.edit');
    Route::put('/reading-tests/{reading_test}', [ReadingTestController::class, 'update'])->name('reading-tests.update');
    Route::delete('/reading-tests/{reading_test}', [ReadingTestController::class, 'destroy'])->name('reading-tests.destroy');
    Route::put('/reading-tests/{id}/restore', [ReadingTestController::class, 'restore'])->name('reading-tests.restore')->whereNumber('id');
    Route::delete('/reading-tests/{id}/force', [ReadingTestController::class, 'forceDestroy'])->name('reading-tests.force-destroy')->whereNumber('id');

    Route::get('/reading-tests/{reading_test}/builder', [ReadingTestBuilderController::class, 'builder'])->name('reading-tests.builder');
    Route::get('/reading-tests/{reading_test}/preview', [ReadingTestBuilderController::class, 'preview'])->name('reading-tests.preview');
    Route::get('/reading-tests/{reading_test}/export-json', [ReadingTestBuilderController::class, 'exportJson'])->name('reading-tests.export-json');
    Route::post('/reading-tests/{reading_test}/import-json', [ReadingTestBuilderController::class, 'importJson'])->name('reading-tests.import-json');
    Route::post('/reading-tests/{reading_test}/passages', [ReadingTestBuilderController::class, 'storePassage'])->name('reading-tests.passages.store');
    Route::put('/reading-tests/{reading_test}/passages/{section}', [ReadingTestBuilderController::class, 'updatePassage'])->name('reading-tests.passages.update');
    Route::post('/reading-tests/{reading_test}/passages/{section}/questions', [ReadingTestBuilderController::class, 'storeQuestion'])->name('reading-tests.questions.store');
    Route::put('/reading-tests/{reading_test}/questions/{question}', [ReadingTestBuilderController::class, 'updateQuestion'])->name('reading-tests.questions.update');
    Route::delete('/reading-tests/{reading_test}/passages/{section}/questions/{question}', [ReadingTestBuilderController::class, 'destroyQuestion'])->name('reading-tests.questions.destroy');

    Route::get('/reading-analytics', [ReadingAnalyticsController::class, 'index'])->name('reading-analytics.index');
    Route::get('/reading-analytics/attempts/{reading_analytic}', [ReadingAnalyticsController::class, 'attempt'])->name('reading-analytics.attempt');
    Route::get('/reading-analytics/{reading_test}/export', [ReadingAnalyticsController::class, 'export'])->name('reading-analytics.export');
    Route::get('/reading-analytics/{reading_test}', [ReadingAnalyticsController::class, 'show'])->name('reading-analytics.show');
});

Route::middleware('permission:question_banks.view')->group(function (): void {
    Route::get('/question-banks/trash', [QuestionBankController::class, 'trash'])->name('question-banks.trash');
    Route::get('/question-banks/export', [QuestionBankController::class, 'export'])->name('question-banks.export');
    Route::get('/question-banks/import', [QuestionBankController::class, 'importForm'])->name('question-banks.import.form');
    Route::post('/question-banks/import', [QuestionBankController::class, 'import'])->name('question-banks.import');
    Route::post('/question-banks/bulk', [QuestionBankController::class, 'bulk'])->name('question-banks.bulk');
    Route::get('/question-banks', [QuestionBankController::class, 'index'])->name('question-banks.index');
    Route::get('/question-banks/create', [QuestionBankController::class, 'create'])->name('question-banks.create');
    Route::post('/question-banks', [QuestionBankController::class, 'store'])->name('question-banks.store');
    Route::get('/question-banks/{question_bank}/edit', [QuestionBankController::class, 'edit'])->name('question-banks.edit');
    Route::put('/question-banks/{question_bank}', [QuestionBankController::class, 'update'])->name('question-banks.update');
    Route::delete('/question-banks/{question_bank}', [QuestionBankController::class, 'destroy'])->name('question-banks.destroy');
    Route::put('/question-banks/{id}/restore', [QuestionBankController::class, 'restore'])->name('question-banks.restore')->whereNumber('id');
    Route::delete('/question-banks/{id}/force', [QuestionBankController::class, 'forceDestroy'])->name('question-banks.force-destroy')->whereNumber('id');
});
