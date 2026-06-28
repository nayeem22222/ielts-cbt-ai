<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Listening\AdminListeningCompletionQuestionController;
use App\Http\Controllers\Admin\Listening\AdminListeningLabellingQuestionController;
use App\Http\Controllers\Admin\Listening\AdminListeningMatchingQuestionController;
use App\Http\Controllers\Admin\Listening\AdminListeningObjectiveQuestionController;
use App\Http\Controllers\Admin\Listening\AdminListeningShortAnswerQuestionController;
use App\Http\Controllers\Admin\Listening\ListeningQuestionBuilderController;
use App\Http\Controllers\Admin\Listening\ListeningQuestionController;
use App\Http\Controllers\Admin\Listening\ListeningQuestionGroupController;
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

    Route::get('/{listeningTest}/builder', [ListeningQuestionBuilderController::class, 'index'])
        ->middleware('permission:listening.questions.view,listening.tests.view')
        ->name('builder.index');

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

        Route::get('/{section}/builder', [ListeningQuestionBuilderController::class, 'sectionBuilder'])
            ->middleware('permission:listening.questions.view,listening.tests.view')
            ->name('builder.index');

        Route::middleware('permission:listening.question_groups.view,listening.tests.view,listening.tests.update')->prefix('{section}/groups')->name('groups.')->group(function (): void {
            Route::get('/', [ListeningQuestionGroupController::class, 'index'])->name('index');
            Route::get('/create', [ListeningQuestionGroupController::class, 'create'])->name('create');
            Route::post('/blank', [ListeningQuestionGroupController::class, 'storeBlank'])->name('store-blank');
            Route::post('/', [ListeningQuestionGroupController::class, 'store'])->name('store');
            Route::post('/reorder', [ListeningQuestionGroupController::class, 'reorder'])->name('reorder');
            Route::get('/{group}', [ListeningQuestionGroupController::class, 'show'])->name('show');
            Route::get('/{group}/edit', [ListeningQuestionGroupController::class, 'edit'])->name('edit');
            Route::put('/{group}', [ListeningQuestionGroupController::class, 'update'])->name('update');
            Route::delete('/{group}', [ListeningQuestionGroupController::class, 'destroy'])->name('destroy');
            Route::post('/{group}/duplicate', [ListeningQuestionGroupController::class, 'duplicate'])->name('duplicate');
            Route::post('/{group}/move-up', [ListeningQuestionGroupController::class, 'moveUp'])->name('move-up');
            Route::post('/{group}/move-down', [ListeningQuestionGroupController::class, 'moveDown'])->name('move-down');

            Route::middleware('permission:listening.questions.view,listening.tests.view,listening.tests.update')->prefix('{group}/questions')->name('questions.')->group(function (): void {
                Route::get('/', [ListeningQuestionController::class, 'index'])->name('index');
                Route::get('/create', [ListeningQuestionController::class, 'create'])->name('create');
                Route::post('/', [ListeningQuestionController::class, 'store'])->name('store');
                Route::post('/bulk-create', [ListeningQuestionController::class, 'bulkCreate'])->name('bulk-create');
                Route::post('/reorder', [ListeningQuestionController::class, 'reorder'])->name('reorder');
                Route::get('/{question}', [ListeningQuestionController::class, 'show'])->name('show');
                Route::get('/{question}/edit', [ListeningQuestionController::class, 'edit'])->name('edit');
                Route::put('/{question}', [ListeningQuestionController::class, 'update'])->name('update');
                Route::delete('/{question}', [ListeningQuestionController::class, 'destroy'])->name('destroy');
            });
        });
    });
});

Route::middleware('permission:listening.questions.view,listening.tests.view')->prefix('listening-question-groups/{group}')->whereNumber('group')->name('listening-question-groups.')->group(function (): void {
    Route::get('/matching-questions', [AdminListeningMatchingQuestionController::class, 'index'])->name('matching-questions.index');
    Route::post('/matching/options', [AdminListeningMatchingQuestionController::class, 'storeOption'])->name('matching.options.store');
    Route::post('/matching/questions', [AdminListeningMatchingQuestionController::class, 'storeQuestion'])->name('matching.questions.store');
    Route::post('/matching/bulk-import', [AdminListeningMatchingQuestionController::class, 'bulkImport'])->name('matching.bulk-import');
    Route::post('/matching/reorder', [AdminListeningMatchingQuestionController::class, 'reorder'])->name('matching.reorder');

    Route::get('/objective-questions', [AdminListeningObjectiveQuestionController::class, 'index'])->name('objective-questions.index');
    Route::post('/objective-questions', [AdminListeningObjectiveQuestionController::class, 'store'])->name('objective-questions.store');
    Route::post('/objective-questions/bulk-import', [AdminListeningObjectiveQuestionController::class, 'bulkImport'])->name('objective-questions.bulk-import');
    Route::post('/objective-questions/reorder', [AdminListeningObjectiveQuestionController::class, 'reorder'])->name('objective-questions.reorder');
    Route::post('/objective-questions/options', [AdminListeningObjectiveQuestionController::class, 'storeOption'])->name('objective-questions.options.store');

    Route::get('/completion-questions', [AdminListeningCompletionQuestionController::class, 'index'])->name('completion-questions.index');
    Route::get('/completion-questions/edit', [AdminListeningCompletionQuestionController::class, 'edit'])->name('completion-questions.edit');
    Route::get('/completion-questions/preview', [AdminListeningCompletionQuestionController::class, 'preview'])->name('completion-questions.preview');
    Route::post('/completion-questions/template', [AdminListeningCompletionQuestionController::class, 'saveTemplate'])->name('completion-questions.template');
    Route::post('/completion-questions/table', [AdminListeningCompletionQuestionController::class, 'saveTable'])->name('completion-questions.table');
    Route::post('/completion-questions/flow-chart', [AdminListeningCompletionQuestionController::class, 'saveFlowChart'])->name('completion-questions.flow-chart');
    Route::post('/completion-questions', [AdminListeningCompletionQuestionController::class, 'storeSentence'])->name('completion-questions.store');
    Route::post('/completion-questions/bulk-import', [AdminListeningCompletionQuestionController::class, 'bulkImport'])->name('completion-questions.bulk-import');
    Route::post('/completion-questions/reorder', [AdminListeningCompletionQuestionController::class, 'reorder'])->name('completion-questions.reorder');
    Route::post('/completion-questions/detect', [AdminListeningCompletionQuestionController::class, 'detect'])->name('completion-questions.detect');

    Route::get('/labelling-questions', [AdminListeningLabellingQuestionController::class, 'index'])->name('labelling-questions.index');
    Route::get('/labelling-questions/edit', [AdminListeningLabellingQuestionController::class, 'edit'])->name('labelling-questions.edit');
    Route::get('/labelling-questions/preview', [AdminListeningLabellingQuestionController::class, 'preview'])->name('labelling-questions.preview');
    Route::get('/labelling-questions/image', [AdminListeningLabellingQuestionController::class, 'showImage'])->name('labelling-questions.image');
    Route::post('/labelling-questions/upload', [AdminListeningLabellingQuestionController::class, 'uploadDiagram'])->name('labelling-questions.upload');
    Route::post('/labelling-questions/labels', [AdminListeningLabellingQuestionController::class, 'saveLabels'])->name('labelling-questions.labels');

    Route::get('/short-answer-questions', [AdminListeningShortAnswerQuestionController::class, 'index'])->name('short-answer-questions.index');
    Route::get('/short-answer-questions/edit', [AdminListeningShortAnswerQuestionController::class, 'edit'])->name('short-answer-questions.edit');
    Route::get('/short-answer-questions/preview', [AdminListeningShortAnswerQuestionController::class, 'preview'])->name('short-answer-questions.preview');
    Route::post('/short-answer-questions', [AdminListeningShortAnswerQuestionController::class, 'store'])->name('short-answer-questions.store');
    Route::post('/short-answer-questions/reorder', [AdminListeningShortAnswerQuestionController::class, 'reorder'])->name('short-answer-questions.reorder');

    Route::middleware('permission:listening.question_groups.update,listening.tests.update')->group(function (): void {
        Route::put('/interaction-settings', [ListeningQuestionGroupController::class, 'updateInteractionSettings'])->name('interaction-settings.update');
    });
});

Route::middleware('permission:listening.questions.update,listening.tests.update')->group(function (): void {
    Route::put('/listening-matching-options/{group}/{option}', [AdminListeningMatchingQuestionController::class, 'updateOption'])->name('listening-matching-options.update')->whereNumber(['group', 'option']);
    Route::delete('/listening-matching-options/{group}/{option}', [AdminListeningMatchingQuestionController::class, 'deleteOption'])->name('listening-matching-options.destroy')->whereNumber(['group', 'option']);
    Route::put('/listening-questions/{question}', [AdminListeningMatchingQuestionController::class, 'updateQuestion'])->name('listening-questions.update')->whereNumber('question');
    Route::delete('/listening-questions/{question}', [AdminListeningMatchingQuestionController::class, 'deleteQuestion'])->name('listening-questions.destroy')->whereNumber('question');

    Route::put('/listening-objective-options/{group}/{option}', [AdminListeningObjectiveQuestionController::class, 'updateOption'])->name('listening-objective-options.update')->whereNumber(['group', 'option']);
    Route::delete('/listening-objective-options/{group}/{option}', [AdminListeningObjectiveQuestionController::class, 'deleteOption'])->name('listening-objective-options.destroy')->whereNumber(['group', 'option']);
    Route::put('/listening-objective-questions/{question}', [AdminListeningObjectiveQuestionController::class, 'update'])->name('listening-objective-questions.update')->whereNumber('question');
    Route::delete('/listening-objective-questions/{question}', [AdminListeningObjectiveQuestionController::class, 'destroy'])->name('listening-objective-questions.destroy')->whereNumber('question');

    Route::put('/listening-completion-questions/{question}', [AdminListeningCompletionQuestionController::class, 'update'])->name('listening-completion-questions.update')->whereNumber('question');
    Route::delete('/listening-completion-questions/{question}', [AdminListeningCompletionQuestionController::class, 'destroy'])->name('listening-completion-questions.destroy')->whereNumber('question');

    Route::put('/listening-labelling-questions/{question}', [AdminListeningLabellingQuestionController::class, 'updateAnswer'])->name('listening-labelling-questions.update')->whereNumber('question');
    Route::delete('/listening-labelling-questions/{question}', [AdminListeningLabellingQuestionController::class, 'destroy'])->name('listening-labelling-questions.destroy')->whereNumber('question');

    Route::put('/listening-short-answer-questions/{question}', [AdminListeningShortAnswerQuestionController::class, 'update'])->name('listening-short-answer-questions.update')->whereNumber('question');
    Route::delete('/listening-short-answer-questions/{question}', [AdminListeningShortAnswerQuestionController::class, 'destroy'])->name('listening-short-answer-questions.destroy')->whereNumber('question');
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
