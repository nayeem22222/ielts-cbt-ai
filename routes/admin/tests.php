<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminReadingCompletionQuestionController;
use App\Http\Controllers\Admin\AdminReadingDiagramQuestionController;
use App\Http\Controllers\Admin\AdminReadingMatchingQuestionController;
use App\Http\Controllers\Admin\AdminReadingObjectiveQuestionController;
use App\Http\Controllers\Admin\AdminReadingShortAnswerQuestionController;
use App\Http\Controllers\Admin\AdminReadingValidationController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\ReadingAnalyticsController;
use App\Http\Controllers\Admin\ReadingPassageController;
use App\Http\Controllers\Admin\ReadingQuestionGroupController;
use App\Http\Controllers\Admin\ReadingTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:tests.view')->group(function (): void {
    Route::get('/reading-tests/trash', [ReadingTestController::class, 'trash'])->name('reading-tests.trash');
    Route::get('/reading-tests/export/csv', [ReadingTestController::class, 'export'])->name('reading-tests.export');
    Route::post('/reading-tests/bulk', [ReadingTestController::class, 'bulk'])->name('reading-tests.bulk');
    Route::get('/reading-tests', [ReadingTestController::class, 'index'])->name('reading-tests.index');
    Route::get('/reading-tests/create', [ReadingTestController::class, 'create'])->name('reading-tests.create');
    Route::post('/reading-tests', [ReadingTestController::class, 'store'])->name('reading-tests.store');
    Route::get('/reading-tests/{readingTest}/edit', [ReadingTestController::class, 'edit'])->name('reading-tests.edit');
    Route::put('/reading-tests/{readingTest}', [ReadingTestController::class, 'update'])->name('reading-tests.update');
    Route::delete('/reading-tests/{readingTest}', [ReadingTestController::class, 'destroy'])->name('reading-tests.destroy');
    Route::post('/reading-tests/{readingTest}/publish', [ReadingTestController::class, 'publish'])->name('reading-tests.publish');
    Route::post('/reading-tests/{readingTest}/unpublish', [ReadingTestController::class, 'unpublish'])->name('reading-tests.unpublish');
    Route::post('/reading-tests/{readingTest}/duplicate', [ReadingTestController::class, 'duplicate'])->name('reading-tests.duplicate');
    Route::post('/reading-tests/{id}/restore', [ReadingTestController::class, 'restore'])->name('reading-tests.restore')->whereNumber('id');
    Route::delete('/reading-tests/{id}/force-delete', [ReadingTestController::class, 'forceDestroy'])->name('reading-tests.force-delete')->whereNumber('id');

    Route::get('/reading-tests/{readingTest}/builder', [ReadingTestController::class, 'builder'])->name('reading-tests.builder');
    Route::get('/reading-tests/{readingTest}/preview', [ReadingTestController::class, 'preview'])->name('reading-tests.preview');
    Route::get('/reading-tests/{readingTest}/validation', [AdminReadingValidationController::class, 'show'])->name('reading-tests.validation');
    Route::post('/reading-tests/{readingTest}/validate', [AdminReadingValidationController::class, 'validate'])->name('reading-tests.validate');
    Route::get('/reading-tests/{readingTest}/preview-full', [AdminReadingValidationController::class, 'previewFull'])->name('reading-tests.preview-full');

    Route::post('/reading-tests/{readingTest}/passages/reorder', [ReadingPassageController::class, 'reorder'])->name('reading-tests.passages.reorder');
    Route::post('/reading-tests/{readingTest}/passages', [ReadingPassageController::class, 'store'])->name('reading-tests.passages.store');
    Route::put('/reading-tests/{readingTest}/passages/{passage}', [ReadingPassageController::class, 'update'])->name('reading-tests.passages.update');
    Route::delete('/reading-tests/{readingTest}/passages/{passage}', [ReadingPassageController::class, 'destroy'])->name('reading-tests.passages.destroy');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/duplicate', [ReadingPassageController::class, 'duplicate'])->name('reading-tests.passages.duplicate');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/move-up', [ReadingPassageController::class, 'moveUp'])->name('reading-tests.passages.move-up');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/move-down', [ReadingPassageController::class, 'moveDown'])->name('reading-tests.passages.move-down');

    Route::post('/reading-tests/{readingTest}/passages/{passage}/groups/reorder', [ReadingQuestionGroupController::class, 'reorder'])->name('reading-tests.passages.groups.reorder');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/groups', [ReadingQuestionGroupController::class, 'store'])->name('reading-tests.passages.groups.store');
    Route::put('/reading-tests/{readingTest}/passages/{passage}/groups/{group}', [ReadingQuestionGroupController::class, 'update'])->name('reading-tests.passages.groups.update');
    Route::delete('/reading-tests/{readingTest}/passages/{passage}/groups/{group}', [ReadingQuestionGroupController::class, 'destroy'])->name('reading-tests.passages.groups.destroy');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/groups/{group}/duplicate', [ReadingQuestionGroupController::class, 'duplicate'])->name('reading-tests.passages.groups.duplicate');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/groups/{group}/move-up', [ReadingQuestionGroupController::class, 'moveUp'])->name('reading-tests.passages.groups.move-up');
    Route::post('/reading-tests/{readingTest}/passages/{passage}/groups/{group}/move-down', [ReadingQuestionGroupController::class, 'moveDown'])->name('reading-tests.passages.groups.move-down');

    Route::prefix('reading-question-groups/{group}')->whereNumber('group')->group(function (): void {
        Route::get('/questions', [AdminReadingMatchingQuestionController::class, 'index'])->name('reading-question-groups.questions.index');
        Route::post('/matching/options', [AdminReadingMatchingQuestionController::class, 'storeOption'])->name('reading-question-groups.matching.options.store');
        Route::post('/matching/questions', [AdminReadingMatchingQuestionController::class, 'storeQuestion'])->name('reading-question-groups.matching.questions.store');
        Route::post('/matching/bulk-import', [AdminReadingMatchingQuestionController::class, 'bulkImport'])->name('reading-question-groups.matching.bulk-import');
        Route::post('/matching/reorder', [AdminReadingMatchingQuestionController::class, 'reorder'])->name('reading-question-groups.matching.reorder');

        Route::get('/objective-questions', [AdminReadingObjectiveQuestionController::class, 'index'])->name('reading-question-groups.objective-questions.index');
        Route::post('/objective-questions', [AdminReadingObjectiveQuestionController::class, 'store'])->name('reading-question-groups.objective-questions.store');
        Route::post('/objective-questions/bulk-import', [AdminReadingObjectiveQuestionController::class, 'bulkImport'])->name('reading-question-groups.objective-questions.bulk-import');
        Route::post('/objective-questions/reorder', [AdminReadingObjectiveQuestionController::class, 'reorder'])->name('reading-question-groups.objective-questions.reorder');

        Route::get('/completion-questions', [AdminReadingCompletionQuestionController::class, 'index'])->name('reading-question-groups.completion-questions.index');
        Route::get('/completion-questions/edit', [AdminReadingCompletionQuestionController::class, 'edit'])->name('reading-question-groups.completion-questions.edit');
        Route::get('/completion-questions/preview', [AdminReadingCompletionQuestionController::class, 'preview'])->name('reading-question-groups.completion-questions.preview');
        Route::post('/completion-questions/template', [AdminReadingCompletionQuestionController::class, 'saveTemplate'])->name('reading-question-groups.completion-questions.template');
        Route::post('/completion-questions/table', [AdminReadingCompletionQuestionController::class, 'saveTable'])->name('reading-question-groups.completion-questions.table');
        Route::post('/completion-questions/flow-chart', [AdminReadingCompletionQuestionController::class, 'saveFlowChart'])->name('reading-question-groups.completion-questions.flow-chart');
        Route::post('/completion-questions', [AdminReadingCompletionQuestionController::class, 'storeSentence'])->name('reading-question-groups.completion-questions.store');
        Route::post('/completion-questions/bulk-import', [AdminReadingCompletionQuestionController::class, 'bulkImport'])->name('reading-question-groups.completion-questions.bulk-import');
        Route::post('/completion-questions/reorder', [AdminReadingCompletionQuestionController::class, 'reorder'])->name('reading-question-groups.completion-questions.reorder');
        Route::post('/completion-questions/detect', [AdminReadingCompletionQuestionController::class, 'detect'])->name('reading-question-groups.completion-questions.detect');

        Route::get('/diagram-questions', [AdminReadingDiagramQuestionController::class, 'index'])->name('reading-question-groups.diagram-questions.index');
        Route::get('/diagram-questions/edit', [AdminReadingDiagramQuestionController::class, 'edit'])->name('reading-question-groups.diagram-questions.edit');
        Route::get('/diagram-questions/preview', [AdminReadingDiagramQuestionController::class, 'preview'])->name('reading-question-groups.diagram-questions.preview');
        Route::get('/diagram-questions/image', [AdminReadingDiagramQuestionController::class, 'showImage'])->name('reading-question-groups.diagram-questions.image');
        Route::post('/diagram-questions/upload', [AdminReadingDiagramQuestionController::class, 'uploadDiagram'])->name('reading-question-groups.diagram-questions.upload');
        Route::post('/diagram-questions/labels', [AdminReadingDiagramQuestionController::class, 'saveLabels'])->name('reading-question-groups.diagram-questions.labels');

        Route::get('/short-answer-questions', [AdminReadingShortAnswerQuestionController::class, 'index'])->name('reading-question-groups.short-answer-questions.index');
        Route::get('/short-answer-questions/edit', [AdminReadingShortAnswerQuestionController::class, 'edit'])->name('reading-question-groups.short-answer-questions.edit');
        Route::get('/short-answer-questions/preview', [AdminReadingShortAnswerQuestionController::class, 'preview'])->name('reading-question-groups.short-answer-questions.preview');
        Route::post('/short-answer-questions', [AdminReadingShortAnswerQuestionController::class, 'store'])->name('reading-question-groups.short-answer-questions.store');
        Route::post('/short-answer-questions/reorder', [AdminReadingShortAnswerQuestionController::class, 'reorder'])->name('reading-question-groups.short-answer-questions.reorder');
    });

    Route::put('/reading-question-options/{option}', [AdminReadingMatchingQuestionController::class, 'updateOption'])->name('reading-question-options.update')->whereNumber('option');
    Route::delete('/reading-question-options/{option}', [AdminReadingMatchingQuestionController::class, 'deleteOption'])->name('reading-question-options.destroy')->whereNumber('option');
    Route::put('/reading-questions/{question}', [AdminReadingMatchingQuestionController::class, 'updateQuestion'])->name('reading-questions.update')->whereNumber('question');
    Route::delete('/reading-questions/{question}', [AdminReadingMatchingQuestionController::class, 'deleteQuestion'])->name('reading-questions.destroy')->whereNumber('question');

    Route::put('/reading-objective-questions/{question}', [AdminReadingObjectiveQuestionController::class, 'update'])->name('reading-objective-questions.update')->whereNumber('question');
    Route::delete('/reading-objective-questions/{question}', [AdminReadingObjectiveQuestionController::class, 'destroy'])->name('reading-objective-questions.destroy')->whereNumber('question');
    Route::post('/reading-objective-questions/{question}/duplicate', [AdminReadingObjectiveQuestionController::class, 'duplicate'])->name('reading-objective-questions.duplicate')->whereNumber('question');
    Route::post('/reading-objective-questions/{question}/options', [AdminReadingObjectiveQuestionController::class, 'storeOption'])->name('reading-objective-questions.options.store')->whereNumber('question');
    Route::put('/reading-objective-options/{option}', [AdminReadingObjectiveQuestionController::class, 'updateOption'])->name('reading-objective-options.update')->whereNumber('option');
    Route::delete('/reading-objective-options/{option}', [AdminReadingObjectiveQuestionController::class, 'deleteOption'])->name('reading-objective-options.destroy')->whereNumber('option');

    Route::put('/reading-completion-questions/{question}', [AdminReadingCompletionQuestionController::class, 'update'])->name('reading-completion-questions.update')->whereNumber('question');
    Route::put('/reading-completion-questions/{question}/answer', [AdminReadingCompletionQuestionController::class, 'updateAnswer'])->name('reading-completion-questions.answer')->whereNumber('question');
    Route::delete('/reading-completion-questions/{question}', [AdminReadingCompletionQuestionController::class, 'destroy'])->name('reading-completion-questions.destroy')->whereNumber('question');

    Route::put('/reading-diagram-questions/{question}', [AdminReadingDiagramQuestionController::class, 'updateAnswer'])->name('reading-diagram-questions.update')->whereNumber('question');
    Route::delete('/reading-diagram-questions/{question}', [AdminReadingDiagramQuestionController::class, 'deleteLabel'])->name('reading-diagram-questions.destroy')->whereNumber('question');

    Route::put('/reading-short-answer-questions/{question}', [AdminReadingShortAnswerQuestionController::class, 'update'])->name('reading-short-answer-questions.update')->whereNumber('question');
    Route::delete('/reading-short-answer-questions/{question}', [AdminReadingShortAnswerQuestionController::class, 'destroy'])->name('reading-short-answer-questions.destroy')->whereNumber('question');

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
