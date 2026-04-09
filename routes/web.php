<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SystemSettingsController as AdminSystemSettingsController;
use App\Http\Controllers\Api\Admin\OrganizationDataController;
use App\Http\Controllers\Api\Workspace\CategoryDataController;
use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\MemberDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Workspace\AppSettingsController;
use App\Http\Controllers\Workspace\AttemptController;
use App\Http\Controllers\Workspace\CategoryController;
use App\Http\Controllers\Workspace\ExamBrowseController;
use App\Http\Controllers\Workspace\ExamController;
use App\Http\Controllers\Workspace\MemberController;
use App\Http\Controllers\Workspace\QuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard — redirects everyone to the admin panel for now
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Admin Panel  (/admin)
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Organizations
        Route::resource('organizations', AdminOrganizationController::class);

        // Users
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // System Settings
        Route::get('settings', [AdminSystemSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSystemSettingsController::class, 'update'])->name('settings.update');

        // Internal API
        Route::get('internal-api/organizations-table', OrganizationDataController::class)->name('internal-api.organizations-table');
    });

    /*
    |----------------------------------------------------------------------
    | Workspace (org-scoped shared features)  (/workspace)
    |----------------------------------------------------------------------
    */
    Route::prefix('workspace')->name('workspace.')->group(function () {
        // App settings
        Route::get('settings', [AppSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AppSettingsController::class, 'update'])->name('settings.update');

        // Categories
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/tree', [CategoryController::class, 'tree'])->name('categories.tree');
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Questions
        Route::resource('questions', QuestionController::class);

        // Exams (Management)
        Route::resource('exams', ExamController::class);
        Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

        // Exams (Taking/Browsing)
        Route::get('exam-browse', [ExamBrowseController::class, 'index'])->name('exam-browse.index');
        Route::get('attempts', [AttemptController::class, 'index'])->name('attempts.index');

        // Members (org-admin section)
        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::post('members', [MemberController::class, 'store'])->name('members.store');
        Route::delete('members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Internal APIs
        Route::get('internal-api/categories-table', CategoryDataController::class)->name('internal-api.categories-table');
        Route::get('internal-api/questions-table', QuestionDataController::class)->name('internal-api.questions-table');
        Route::get('internal-api/exams-table', ExamDataController::class)->name('internal-api.exams-table');
        Route::get('internal-api/members-table', MemberDataController::class)->name('internal-api.members-table');
    });
});
