<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\PackageController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:packages.view')->group(function (): void {
    Route::get('/packages/trash', [PackageController::class, 'trash'])->name('packages.trash');
    Route::get('/packages/export', [PackageController::class, 'export'])->name('packages.export');
    Route::get('/packages/import', [PackageController::class, 'importForm'])->name('packages.import.form');
    Route::post('/packages/import', [PackageController::class, 'import'])->name('packages.import');
    Route::post('/packages/bulk', [PackageController::class, 'bulk'])->name('packages.bulk');
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/packages/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    Route::put('/packages/{id}/restore', [PackageController::class, 'restore'])->name('packages.restore')->whereNumber('id');
    Route::delete('/packages/{id}/force', [PackageController::class, 'forceDestroy'])->name('packages.force-destroy')->whereNumber('id');
});
