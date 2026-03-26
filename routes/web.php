<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SystemSettingsController as AdminSystemSettingsController;
use App\Http\Controllers\OrgAdmin\DashboardController as OrgAdminDashboardController;
use App\Http\Controllers\OrgAdmin\ExamController as OrgAdminExamController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\Editor\QuestionController as EditorQuestionController;
use App\Http\Controllers\Viewer\DashboardController as ViewerDashboardController;
use App\Http\Controllers\Viewer\ExamBrowseController as ViewerExamBrowseController;
use App\Http\Controllers\Viewer\AttemptController as ViewerAttemptController;
use App\Http\Controllers\Api\Admin\OrganizationDataController;
use App\Http\Controllers\Api\Workspace\CategoryDataController;
use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\MemberDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Workspace\AppSettingsController;
use App\Http\Controllers\Workspace\CategoryController;
use App\Http\Controllers\Workspace\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__.'/auth.php';

// Route::middleware(['auth', 'verified', 'set.org'])->group(function () {
    Route::get('/dashboard', function () {
        // Temporary development mode:
        // send everyone to the shared admin dashboard while role routing is disabled.
        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    Route::get('/no-organization', function () {
        return view('no-organization');
    })->name('no-organization');

    Route::post('/current-organization', OrganizationSwitchController::class)
        ->name('organization.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

Route::middleware(['auth', /* 'verified', 'role:admin' */])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('organizations', AdminOrganizationController::class);
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::get('settings', [AdminSystemSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSystemSettingsController::class, 'update'])->name('settings.update');

        Route::get('internal-api/organizations-table', OrganizationDataController::class)->name('internal-api.organizations-table');
    });

Route::middleware(['auth', /* 'verified', 'set.org', 'org.role:org_admin' */])
    ->prefix('org-admin')
    ->name('org-admin.')
    ->group(function () {
        Route::get('/', [OrgAdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('exams', OrgAdminExamController::class);
        Route::patch('exams/{exam}/publish', [OrgAdminExamController::class, 'publish'])->name('exams.publish');
        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::post('members', [MemberController::class, 'store'])->name('members.store');
        Route::delete('members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
    });

Route::middleware(['auth', /* 'verified', 'set.org', 'org.role:editor|org_admin' */])
    ->prefix('editor')
    ->name('editor.')
    ->group(function () {
        Route::get('/', [EditorDashboardController::class, 'index'])->name('dashboard');
        Route::resource('questions', EditorQuestionController::class);
    });

Route::middleware(['auth', /* 'verified', 'set.org', 'org.role:viewer|editor|org_admin' */])
    ->prefix('viewer')
    ->name('viewer.')
    ->group(function () {
        Route::get('/', [ViewerDashboardController::class, 'index'])->name('dashboard');
        Route::get('exams', [ViewerExamBrowseController::class, 'index'])->name('exams.index');
        Route::get('attempts', [ViewerAttemptController::class, 'index'])->name('attempts.index');
    });

Route::middleware(['auth', /* 'verified', 'set.org', 'org.role:viewer|editor|org_admin' */])
    ->prefix('workspace')
    ->name('workspace.')
    ->group(function () {
        Route::get('settings', [AppSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AppSettingsController::class, 'update'])->name('settings.update');
    });

Route::middleware(['auth', /* 'verified', 'set.org', 'org.role:editor|org_admin' */])
    ->prefix('workspace')
    ->name('workspace.')
    ->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/tree', [CategoryController::class, 'tree'])->name('categories.tree');
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('internal-api/categories-table', CategoryDataController::class)->name('internal-api.categories-table');
        Route::get('internal-api/questions-table', QuestionDataController::class)->name('internal-api.questions-table');
        Route::get('internal-api/exams-table', ExamDataController::class)->name('internal-api.exams-table');
        Route::get('internal-api/members-table', MemberDataController::class)->name('internal-api.members-table');
    });
