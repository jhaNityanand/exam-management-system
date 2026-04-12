<?php

use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\Backend\CandidateController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExamController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\QuestionController;
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

// Auth routes (Breeze/Jetstream)
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Backend (Admin) Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('internal-api/exams-table', ExamDataController::class)->name('internal-api.exams-table');
    Route::get('internal-api/questions-table', QuestionDataController::class)->name('internal-api.questions-table');

    Route::resource('categories', CategoryController::class);
    Route::resource('questions', QuestionController::class);
    Route::resource('exams', ExamController::class);
    Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('candidates', CandidateController::class)->only(['index']);
    Route::resource('notifications', NotificationController::class)->only(['index']);
    Route::resource('logs', LogController::class)->only(['index']);
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::redirect('/profile', '/admin/profile')->middleware(['auth'])->name('profile.legacy');
