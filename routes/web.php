<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.landing')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'pages.auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::view('/register', 'pages.auth.register')->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::view('/forgot-password', 'pages.auth.forgot-password')->name('password.request');
Route::view('/reset-password', 'pages.auth.reset-password')->name('password.reset');
Route::view('/email/verify', 'pages.auth.verify-email')->name('verification.notice');
Route::view('/two-factor-challenge', 'pages.auth.two-factor')->name('two-factor.login');
Route::view('/confirm-password', 'pages.auth.confirm-password')->name('password.confirm');

Route::view('/ui', 'pages.ui.index')->name('ui.index');

Route::middleware(['auth', 'role:student'])->group(function (): void {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
});

Route::middleware(['auth', 'role:teacher'])->group(function (): void {
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::redirect('/student', '/student/dashboard');
Route::redirect('/teacher', '/teacher/dashboard');
Route::redirect('/admin', '/admin/dashboard');
Route::redirect('/dashboard', '/student/dashboard')->name('dashboard');

Route::view('/courses', 'pages.courses.index')->name('courses.index');
Route::view('/courses/show', 'pages.courses.show')->name('courses.show');
Route::view('/exam/reading', 'pages.exams.reading')->name('exam.reading');
Route::view('/exam/listening', 'pages.exams.listening')->name('exam.listening');
Route::view('/exam/writing', 'pages.exams.writing')->name('exam.writing');
Route::view('/exam/speaking', 'pages.exams.speaking')->name('exam.speaking');
