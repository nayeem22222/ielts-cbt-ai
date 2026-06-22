<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\DeviceController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.landing')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'pages.auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::view('/register', 'pages.auth.register')->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/confirm-password', [ConfirmPasswordController::class, 'create'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmPasswordController::class, 'store'])->name('password.confirm.store');

    Route::middleware('verified')->prefix('account')->name('account.')->group(function (): void {
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices/{device}/trust', [DeviceController::class, 'trust'])->name('devices.trust');
        Route::delete('/devices/{device}/trust', [DeviceController::class, 'untrust'])->name('devices.untrust');
        Route::delete('/devices/sessions/others', [DeviceController::class, 'destroyOthers'])->name('devices.sessions.destroy-others');
        Route::delete('/devices/sessions/{session}', [DeviceController::class, 'destroySession'])->name('devices.sessions.destroy');

        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
        Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    });
});

Route::view('/two-factor-challenge', 'pages.auth.two-factor')->name('two-factor.login');

Route::view('/ui', 'pages.ui.index')->name('ui.index');

Route::middleware(['auth', 'verified', 'role:student'])->group(function (): void {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
});

Route::middleware(['auth', 'verified', 'role:teacher'])->group(function (): void {
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
});

Route::middleware(['auth', 'verified', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users/trash', [UserController::class, 'trash'])->name('users.trash');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import.form');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
    Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/email-verification', [UserController::class, 'verifyEmail'])->name('users.email.verify');
    Route::delete('/users/{user}/email-verification', [UserController::class, 'unverifyEmail'])->name('users.email.unverify');
    Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions'])->name('users.permissions.sync');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore')->whereNumber('id');
    Route::delete('/users/{id}/force', [UserController::class, 'forceDestroy'])->name('users.force-destroy')->whereNumber('id');

    Route::middleware('permission:roles.view')->group(function (): void {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
    });

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permissions.view')
        ->name('permissions.index');

    Route::middleware('permission:settings.view')->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    });

    Route::middleware('permission:settings.update')->group(function (): void {
        Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/backup/run', [SettingsController::class, 'runBackup'])->name('settings.backup.run');
    });
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
