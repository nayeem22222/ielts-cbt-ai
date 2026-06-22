<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\StudentEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:enrollments.view')->group(function (): void {
    Route::get('/enrollments', [StudentEnrollmentController::class, 'index'])->name('enrollments.index');
});

Route::middleware('permission:enrollments.create')->group(function (): void {
    Route::post('/enrollments', [StudentEnrollmentController::class, 'store'])->name('enrollments.store');
});

Route::middleware('permission:enrollments.update')->group(function (): void {
    Route::put('/enrollments/{enrollment}/activate', [StudentEnrollmentController::class, 'activate'])->name('enrollments.activate');
    Route::put('/enrollments/{enrollment}/cancel', [StudentEnrollmentController::class, 'cancel'])->name('enrollments.cancel');
});
