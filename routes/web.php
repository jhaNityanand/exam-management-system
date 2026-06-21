<?php

use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\Backend\CandidateController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExamController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\QuestionController;
use App\Http\Controllers\Backend\QuestionCategory\QuestionCategoryController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth routes (Breeze / Jetstream)
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Backend (Admin) Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profile ───────────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Internal API (DataTable JSON endpoints) ───────────────────────────────
    Route::get('internal-api/exams-table',     ExamDataController::class)->name('internal-api.exams-table');
    Route::get('internal-api/questions-table', QuestionDataController::class)->name('internal-api.questions-table');

    // ── Questions Module ──────────────────────────────────────────────────────
    Route::prefix('questions')->name('questions.')->group(function () {

        // Question Categories sub-module
        Route::resource('categories', QuestionCategoryController::class)
            ->names('categories');
    });

    // Questions resource (standalone)
    Route::resource('questions', QuestionController::class);

    // ── Exams ─────────────────────────────────────────────────────────────────
    Route::resource('exams', ExamController::class);
    Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

    // ── Settings ─────────────────────────────────────────────────────────────
    Route::get('settings',  [SettingController::class, 'edit'])->name('settings.index');
    Route::put('settings',  [SettingController::class, 'update'])->name('settings.update');

    // ── Read-only resources ───────────────────────────────────────────────────
    Route::resource('candidates',    CandidateController::class)->only(['index']);
    Route::resource('notifications', NotificationController::class)->only(['index']);
    Route::resource('logs',          LogController::class)->only(['index']);
});

/*
|--------------------------------------------------------------------------
| Convenience Redirects
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

Route::redirect('/profile', '/admin/profile')
    ->middleware(['auth'])
    ->name('profile.legacy');
