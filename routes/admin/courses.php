<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseSectionController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\LessonResourceController;
use Illuminate\Support\Facades\Route;

$courseResources = [
    ['course-categories', 'course-categories', CourseCategoryController::class, 'course_category'],
    ['courses', 'courses', CourseController::class, 'course'],
    ['course-sections', 'course-sections', CourseSectionController::class, 'course_section'],
    ['lessons', 'lessons', LessonController::class, 'lesson'],
    ['lesson-resources', 'lesson-resources', LessonResourceController::class, 'lesson_resource'],
];

Route::middleware('permission:courses.view')->group(function () use ($courseResources): void {
    foreach ($courseResources as [$uri, $name, $controller, $parameter]) {
        Route::get("/{$uri}/trash", [$controller, 'trash'])->name("{$name}.trash");
        Route::get("/{$uri}/export", [$controller, 'export'])->name("{$name}.export");
        Route::get("/{$uri}/import", [$controller, 'importForm'])->name("{$name}.import.form");
        Route::post("/{$uri}/import", [$controller, 'import'])->name("{$name}.import");
        Route::post("/{$uri}/bulk", [$controller, 'bulk'])->name("{$name}.bulk");
        Route::get("/{$uri}", [$controller, 'index'])->name("{$name}.index");
        Route::get("/{$uri}/create", [$controller, 'create'])->name("{$name}.create");
        Route::post("/{$uri}", [$controller, 'store'])->name("{$name}.store");
        Route::get("/{$uri}/{{$parameter}}/edit", [$controller, 'edit'])->name("{$name}.edit");
        Route::put("/{$uri}/{{$parameter}}", [$controller, 'update'])->name("{$name}.update");
        Route::delete("/{$uri}/{{$parameter}}", [$controller, 'destroy'])->name("{$name}.destroy");
        Route::put("/{$uri}/{id}/restore", [$controller, 'restore'])->name("{$name}.restore")->whereNumber('id');
        Route::delete("/{$uri}/{id}/force", [$controller, 'forceDestroy'])->name("{$name}.force-destroy")->whereNumber('id');
    }
});
