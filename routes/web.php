<?php

use App\Http\Controllers\Backend\GalleryController;
use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\Backend\CandidateController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExamController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\QuestionController;
use App\Http\Controllers\Backend\QuestionCategoryController;
use App\Http\Controllers\Backend\ExamCategoryController;
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

// Auth routes (Laravel Breeze)
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

    Route::post('editor/media', [GalleryController::class, 'storeEditor'])->name('editor.media.store');

    // ── Gallery ───────────────────────────────────────────────────────────────
    Route::prefix('gallery')->name('gallery.')->group(function () {
        Route::get('/', [GalleryController::class, 'index'])->name('index');
        Route::get('/data', [GalleryController::class, 'data'])->name('data');
        Route::get('/stats', [GalleryController::class, 'stats'])->name('stats');
        Route::post('/', [GalleryController::class, 'store'])->name('store');
        Route::post('/bulk-delete', [GalleryController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-restore', [GalleryController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [GalleryController::class, 'bulkForceDelete'])->name('bulk-force-delete');
        Route::get('/{id}', [GalleryController::class, 'show'])->name('show')->whereNumber('id');
        Route::get('/{id}/download', [GalleryController::class, 'download'])->name('download')->whereNumber('id');
        Route::put('/{id}', [GalleryController::class, 'update'])->name('update')->whereNumber('id');
        Route::patch('/{id}/restore', [GalleryController::class, 'restore'])->name('restore')->whereNumber('id');
        Route::delete('/{id}', [GalleryController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::delete('/{id}/force', [GalleryController::class, 'forceDestroy'])->name('force-destroy')->whereNumber('id');
    });

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
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::resource('categories', ExamCategoryController::class)->names('categories');
    });
    Route::get('api/question-bank/categories', [ExamController::class, 'apiCategories'])->name('api.question-bank.categories');
    Route::get('api/question-bank/questions', [ExamController::class, 'apiQuestions'])->name('api.question-bank.questions');
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
