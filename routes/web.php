<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Demo\AuthDemoController;

Route::view('/', 'pages.landing')->name('home');

Route::view('/login', 'pages.auth.login')->name('login');
Route::post('/login', [AuthDemoController::class, 'login'])->name('login.store');
Route::view('/register', 'pages.auth.register')->name('register');
Route::post('/register', [AuthDemoController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthDemoController::class, 'logout'])->name('logout');
Route::view('/forgot-password', 'pages.auth.forgot-password')->name('password.request');
Route::view('/reset-password', 'pages.auth.reset-password')->name('password.reset');
Route::view('/email/verify', 'pages.auth.verify-email')->name('verification.notice');
Route::view('/two-factor-challenge', 'pages.auth.two-factor')->name('two-factor.login');
Route::view('/confirm-password', 'pages.auth.confirm-password')->name('password.confirm');

Route::view('/ui', 'pages.ui.index')->name('ui.index');
Route::view('/student', 'pages.student.dashboard')->name('student.dashboard');
Route::view('/dashboard', 'pages.student.dashboard')->name('dashboard');
Route::view('/teacher', 'pages.teacher.dashboard')->name('teacher.dashboard');
Route::view('/admin', 'pages.admin.dashboard')->name('admin.dashboard');
Route::view('/courses', 'pages.courses.index')->name('courses.index');
Route::view('/courses/show', 'pages.courses.show')->name('courses.show');
Route::view('/exam/reading', 'pages.exams.reading')->name('exam.reading');
Route::view('/exam/listening', 'pages.exams.listening')->name('exam.listening');
Route::view('/exam/writing', 'pages.exams.writing')->name('exam.writing');
Route::view('/exam/speaking', 'pages.exams.speaking')->name('exam.speaking');
