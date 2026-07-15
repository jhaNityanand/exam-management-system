<?php

use App\Http\Controllers\Backend\GalleryController;
use App\Http\Controllers\Api\Workspace\BlogDataController;
use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\NewsDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\BlogCategoryController;
use App\Http\Controllers\Backend\NewsController;
use App\Http\Controllers\Backend\NewsCategoryController;
use App\Http\Controllers\Backend\CandidateController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExamController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\QuestionController;
use App\Http\Controllers\Backend\QuestionCategoryController;
use App\Http\Controllers\Backend\ExamCategoryController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\BlogController as FrontendBlogController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\ExamController as FrontendExamController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\NewsController as FrontendNewsController;
use App\Http\Controllers\Frontend\NewsletterController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/exams', [FrontendExamController::class, 'index'])->name('frontend.exams.index');
Route::get('/exams/{exam:slug}', [FrontendExamController::class, 'show'])->name('frontend.exams.show');

Route::get('/blogs', [FrontendBlogController::class, 'index'])->name('frontend.blogs.index');
Route::get('/blogs/category/{slug}', [FrontendBlogController::class, 'category'])->name('frontend.blogs.category');
Route::get('/blogs/tag/{slug}', [FrontendBlogController::class, 'tag'])->name('frontend.blogs.tag');
Route::get('/blogs/{blog:slug}', [FrontendBlogController::class, 'show'])->name('frontend.blogs.show');

Route::get('/news', [FrontendNewsController::class, 'index'])->name('frontend.news.index');
Route::get('/news/trending', [FrontendNewsController::class, 'trending'])->name('frontend.news.trending');
Route::get('/news/category/{slug}', [FrontendNewsController::class, 'category'])->name('frontend.news.category');
Route::get('/news/{news:slug}', [FrontendNewsController::class, 'show'])->name('frontend.news.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('frontend.categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('frontend.categories.show');

Route::get('/search', [SearchController::class, 'index'])->name('frontend.search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('frontend.search.suggest');

Route::post('/newsletter', [NewsletterController::class, 'store'])->name('frontend.newsletter.store');
Route::post('/contact', [PageController::class, 'contact'])->name('frontend.contact.store');

Route::middleware('auth')->prefix('account')->name('frontend.account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/exams', [AccountController::class, 'exams'])->name('exams');
    Route::get('/results', [AccountController::class, 'results'])->name('results');
    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
});

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
        Route::post('/commit', [GalleryController::class, 'commit'])->name('commit');
        Route::post('/bulk-delete', [GalleryController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-restore', [GalleryController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [GalleryController::class, 'bulkForceDelete'])->name('bulk-force-delete');
        Route::get('/{id}', [GalleryController::class, 'show'])->name('show')->whereNumber('id');
        Route::get('/{id}/download', [GalleryController::class, 'download'])->name('download')->whereNumber('id');
        Route::put('/{id}', [GalleryController::class, 'update'])->name('update')->whereNumber('id');
        Route::post('/{id}/edit', [GalleryController::class, 'saveEdit'])->name('edit')->whereNumber('id');
        Route::post('/{id}/revert', [GalleryController::class, 'revert'])->name('revert')->whereNumber('id');
        Route::patch('/{id}/restore', [GalleryController::class, 'restore'])->name('restore')->whereNumber('id');
        Route::delete('/{id}', [GalleryController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::delete('/{id}/force', [GalleryController::class, 'forceDestroy'])->name('force-destroy')->whereNumber('id');
    });

    // ── Internal API (DataTable JSON endpoints) ───────────────────────────────
    Route::get('internal-api/exams-table',     ExamDataController::class)->name('internal-api.exams-table');
    Route::get('internal-api/questions-table', QuestionDataController::class)->name('internal-api.questions-table');
    Route::get('internal-api/blogs-table',     BlogDataController::class)->name('internal-api.blogs-table');
    Route::get('internal-api/news-table',      NewsDataController::class)->name('internal-api.news-table');

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

    // ── Blogs ─────────────────────────────────────────────────────────────────
    Route::prefix('blogs')->name('blogs.')->group(function () {
        Route::resource('categories', BlogCategoryController::class)->names('categories');
        Route::post('bulk-destroy', [BlogController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('bulk-restore', [BlogController::class, 'bulkRestore'])->name('bulk-restore');
        Route::patch('{blog}/restore', [BlogController::class, 'restore'])->name('restore')->withTrashed();
    });
    Route::resource('blogs', BlogController::class);

    // ── News ──────────────────────────────────────────────────────────────────
    Route::prefix('news')->name('news.')->group(function () {
        Route::resource('categories', NewsCategoryController::class)->names('categories');
        Route::post('bulk-destroy', [NewsController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('bulk-restore', [NewsController::class, 'bulkRestore'])->name('bulk-restore');
        Route::patch('{news}/restore', [NewsController::class, 'restore'])->name('restore')->withTrashed();
    });
    Route::resource('news', NewsController::class);

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

/*
|--------------------------------------------------------------------------
| CMS pages (root slugs) — MUST remain last
|--------------------------------------------------------------------------
|
| Keep these after all fixed frontend/auth/admin routes.
| Slug pattern must NOT use ".+" or it will match multi-segment URIs
| like /admin/exams/create (custom requirements replace [^/]+).
|
*/

Route::redirect('/pages/{slug}', '/{slug}', 301)->where('slug', '[A-Za-z0-9\-]+');

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('frontend.pages.show');
