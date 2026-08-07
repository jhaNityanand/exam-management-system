<?php

use App\Http\Controllers\Backend\GalleryController;
use App\Http\Controllers\Api\Workspace\BlogDataController;
use App\Http\Controllers\Api\Workspace\ExamDataController;
use App\Http\Controllers\Api\Workspace\NewsDataController;
use App\Http\Controllers\Api\Workspace\QuestionDataController;
use App\Http\Controllers\Api\Workspace\CandidateDataController;
use App\Http\Controllers\Api\Workspace\ExamAttempterDataController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\BlogCategoryController;
use App\Http\Controllers\Backend\NewsController;
use App\Http\Controllers\Backend\NewsCategoryController;
use App\Http\Controllers\Backend\CandidateController;
use App\Http\Controllers\Backend\TransactionController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExamController;
use App\Http\Controllers\Backend\ExamAttemptListController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\QuestionController;
use App\Http\Controllers\Backend\QuestionCategoryController;
use App\Http\Controllers\Backend\ExamCategoryController;
use App\Http\Controllers\Backend\AdvertisementController;
use App\Http\Controllers\Backend\Settings\CacheOptimizationController;
use App\Http\Controllers\Backend\Settings\EmailSettingController;
use App\Http\Controllers\Backend\Settings\IntegrationsSettingController;
use App\Http\Controllers\Backend\Settings\MaintenanceSettingController;
use App\Http\Controllers\Backend\Settings\OrganizationSettingController;
use App\Http\Controllers\Backend\Settings\OrganizationFaqController;
use App\Http\Controllers\Backend\Settings\OrganizationMemberController;
use App\Http\Controllers\Backend\Settings\SecuritySettingController;
use App\Http\Controllers\Backend\Settings\SeoSettingController;
use App\Http\Controllers\Backend\Settings\LlmManagementController;
use App\Http\Controllers\Frontend\AuthorController;
use App\Http\Controllers\Backend\SlugController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\BlogController as FrontendBlogController;
use App\Http\Controllers\Frontend\CandidateAttemptController;
use App\Http\Controllers\Frontend\CandidateExamController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\ExamController as FrontendExamController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\NewsController as FrontendNewsController;
use App\Http\Controllers\Frontend\NewsletterController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\SitemapController;
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

Route::get('/questions', [\App\Http\Controllers\Frontend\QuestionController::class, 'index'])->name('frontend.questions.index');
Route::get('/questions/categories', [\App\Http\Controllers\Frontend\QuestionController::class, 'categories'])->name('frontend.questions.categories');
Route::get('/questions/category/{slug}', [\App\Http\Controllers\Frontend\QuestionController::class, 'category'])->name('frontend.questions.category');
Route::get('/questions/{question:slug}', [\App\Http\Controllers\Frontend\QuestionController::class, 'show'])->name('frontend.questions.show');
Route::redirect('/question/categories', '/questions/categories', 301);
Route::get('/question/category/{slug}', function (string $slug) {
    return redirect()->route('frontend.questions.category', $slug, 301);
});
Route::redirect('/question', '/questions', 301);
Route::get('/question/{slug}', function (string $slug) {
    return redirect()->route('frontend.questions.show', $slug, 301);
});
Route::redirect('/about', '/about-us', 301);
Route::redirect('/contact', '/contact-us', 301);

Route::middleware('auth')->group(function () {
    Route::get('/exams/{exam:slug}/rules', [CandidateExamController::class, 'rules'])->name('frontend.exams.rules');
    Route::post('/exams/{exam:slug}/rules/agree', [CandidateExamController::class, 'agreeRules'])->name('frontend.exams.rules.agree');
    Route::get('/exams/{exam:slug}/prepare', [CandidateExamController::class, 'prepare'])->name('frontend.exams.prepare');
    Route::get('/exams/{exam:slug}/started', [CandidateExamController::class, 'started'])->name('frontend.exams.started');
    Route::post('/exams/{exam:slug}/verification', [CandidateExamController::class, 'storeVerification'])->name('frontend.exams.verification');
    Route::post('/exams/{exam:slug}/attempts', [CandidateExamController::class, 'start'])->name('frontend.exams.attempts.start');
    Route::post('/exams/{exam:slug}/purchase', [CandidateExamController::class, 'purchase'])->name('frontend.exams.purchase');

    Route::post('/feedback', [\App\Http\Controllers\Frontend\FeedbackController::class, 'store'])->name('frontend.feedback.store');
    Route::post('/feedback/skip', [\App\Http\Controllers\Frontend\FeedbackController::class, 'skip'])->name('frontend.feedback.skip');

    Route::get('/attempts/{attempt}', [CandidateAttemptController::class, 'show'])->name('frontend.attempts.show');
    Route::match(['patch', 'post'], '/attempts/{attempt}/answers', [CandidateAttemptController::class, 'saveAnswers'])
        ->middleware('throttle:60,1')
        ->name('frontend.attempts.answers');
    Route::post('/attempts/{attempt}/heartbeat', [CandidateAttemptController::class, 'heartbeat'])
        ->middleware('throttle:60,1')
        ->name('frontend.attempts.heartbeat');
    Route::post('/attempts/{attempt}/events', [CandidateAttemptController::class, 'events'])
        ->middleware('throttle:60,1')
        ->name('frontend.attempts.events');
    Route::post('/attempts/{attempt}/submit', [CandidateAttemptController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('frontend.attempts.submit');
    Route::get('/attempts/{attempt}/result', [CandidateAttemptController::class, 'result'])->name('frontend.attempts.result');
    Route::get('/attempts/{attempt}/result/data', [CandidateAttemptController::class, 'resultData'])->name('frontend.attempts.result.data');
    Route::get('/attempts/{attempt}/review', [CandidateAttemptController::class, 'review'])->name('frontend.attempts.review');
    Route::get('/attempts/{attempt}/review/data', [CandidateAttemptController::class, 'reviewData'])->name('frontend.attempts.review.data');
});
Route::get('/blogs', [FrontendBlogController::class, 'index'])->name('frontend.blogs.index');
Route::get('/blogs/category/{slug}', [FrontendBlogController::class, 'category'])->name('frontend.blogs.category');
Route::get('/blogs/tag/{slug}', [FrontendBlogController::class, 'tag'])->name('frontend.blogs.tag');
Route::get('/blogs/{blog:slug}', [FrontendBlogController::class, 'show'])->name('frontend.blogs.show');

Route::get('/news', [FrontendNewsController::class, 'index'])->name('frontend.news.index');
Route::get('/news/trending', [FrontendNewsController::class, 'trending'])->name('frontend.news.trending');
Route::get('/news/category/{slug}', [FrontendNewsController::class, 'category'])->name('frontend.news.category');
Route::get('/news/tag/{slug}', [FrontendNewsController::class, 'tag'])->name('frontend.news.tag');
Route::get('/news/{news:slug}', [FrontendNewsController::class, 'show'])->name('frontend.news.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('frontend.categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('frontend.categories.show');

Route::get('/authors', [AuthorController::class, 'index'])->name('frontend.authors.index');
Route::get('/authors/{author:slug}', [AuthorController::class, 'show'])->name('frontend.authors.show');

Route::get('/faqs', [FaqController::class, 'index'])->name('frontend.faqs.index');
Route::get('/sitemap', [SitemapController::class, 'index'])->name('frontend.sitemap');

Route::get('/search', [SearchController::class, 'index'])->name('frontend.search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('frontend.search.suggest');

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('frontend.newsletter.store');
Route::post('/contact', [PageController::class, 'contact'])
    ->middleware('throttle:8,1')
    ->name('frontend.contact.store');

Route::middleware('auth')->prefix('account')->name('frontend.account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/exams', [AccountController::class, 'exams'])->name('exams');
    Route::get('/results', [AccountController::class, 'results'])->name('results');

    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::get('/profile/data', [AccountController::class, 'profileData'])->name('profile.data');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');

    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
    Route::get('/settings/data', [AccountController::class, 'settingsData'])->name('settings.data');
    Route::put('/settings', [AccountController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/account', [AccountController::class, 'updateAccountSettings'])->name('settings.account');
    Route::post('/settings/password', [AccountController::class, 'updatePassword'])->name('settings.password');
    Route::delete('/settings/account', [AccountController::class, 'destroyAccount'])->name('settings.destroy');

    Route::get('/invoices', [AccountController::class, 'invoices'])->name('invoices');
    Route::get('/activity', [AccountController::class, 'activity'])->name('activity');
});

// Auth routes (Laravel Breeze)
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Backend (Admin) Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

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
    Route::get('internal-api/candidates-table', CandidateDataController::class)->name('internal-api.candidates-table');
    Route::get('internal-api/exams/{exam}/attempters', [ExamAttempterDataController::class, 'index'])->name('internal-api.exam-attempters')->whereNumber('exam');
    Route::get('internal-api/exams/{exam}/attempters/{user}/attempts', [ExamAttempterDataController::class, 'attempts'])->name('internal-api.exam-attempter-attempts')->whereNumber(['exam', 'user']);
    Route::get('internal-api/exams/{exam}/attempters/{user}/verification', [ExamAttempterDataController::class, 'verification'])->name('internal-api.exam-attempter-verification')->whereNumber(['exam', 'user']);
    Route::get('slug/resolve', [SlugController::class, 'resolve'])->name('slug.resolve');

    // ── Questions Module ──────────────────────────────────────────────────────
    Route::prefix('questions')->name('questions.')->group(function () {

        // Question Categories sub-module
        Route::post('categories/bulk-destroy', [QuestionCategoryController::class, 'bulkDestroyCategories'])->name('categories.bulk-destroy');
        Route::post('categories/bulk-restore', [QuestionCategoryController::class, 'bulkRestoreCategories'])->name('categories.bulk-restore');
        Route::patch('categories/bulk-status', [QuestionCategoryController::class, 'bulkUpdateCategoryStatus'])->name('categories.bulk-status');
        Route::patch('categories/{id}/restore', [QuestionCategoryController::class, 'restoreCategory'])->name('categories.restore')->whereNumber('id');
        Route::resource('categories', QuestionCategoryController::class)
            ->names('categories');
    });

    // Questions resource (standalone)
    Route::post('questions/bulk-destroy', [QuestionController::class, 'bulkDestroy'])->name('questions.bulk-destroy');
    Route::post('questions/bulk-restore', [QuestionController::class, 'bulkRestore'])->name('questions.bulk-restore');
    Route::patch('questions/bulk-status', [QuestionController::class, 'bulkUpdateStatus'])->name('questions.bulk-status');
    Route::post('questions/imports', [QuestionController::class, 'startImport'])->name('questions.imports.start');
    Route::get('questions/imports/{id}', [QuestionController::class, 'importDetails'])->name('questions.imports.show')->whereNumber('id');
    Route::get('questions/imports/{id}/download', [QuestionController::class, 'downloadImport'])->name('questions.imports.download')->whereNumber('id');
    Route::patch('questions/imports/{id}/complete', [QuestionController::class, 'completeImport'])->name('questions.imports.complete')->whereNumber('id');
    Route::post('questions/import', [QuestionController::class, 'import'])->name('questions.import');
    Route::patch('questions/{id}/restore', [QuestionController::class, 'restore'])->name('questions.restore')->whereNumber('id');
    Route::resource('questions', QuestionController::class);

    // ── Exams ─────────────────────────────────────────────────────────────────
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::post('categories/bulk-destroy', [ExamCategoryController::class, 'bulkDestroyCategories'])->name('categories.bulk-destroy');
        Route::post('categories/bulk-restore', [ExamCategoryController::class, 'bulkRestoreCategories'])->name('categories.bulk-restore');
        Route::patch('categories/bulk-status', [ExamCategoryController::class, 'bulkUpdateCategoryStatus'])->name('categories.bulk-status');
        Route::patch('categories/{id}/restore', [ExamCategoryController::class, 'restoreCategory'])->name('categories.restore')->whereNumber('id');
        Route::resource('categories', ExamCategoryController::class)->names('categories');
    });
    Route::get('api/question-bank/categories', [ExamController::class, 'apiCategories'])->name('api.question-bank.categories');
    Route::get('api/question-bank/counts', [ExamController::class, 'apiQuestionCounts'])->name('api.question-bank.counts');
    Route::get('api/question-bank/questions', [ExamController::class, 'apiQuestions'])->name('api.question-bank.questions');
    Route::get('api/question-bank/random', [ExamController::class, 'apiRandomQuestions'])->name('api.question-bank.random');
    Route::post('exams/bulk-destroy', [ExamController::class, 'bulkDestroy'])->name('exams.bulk-destroy');
    Route::post('exams/bulk-restore', [ExamController::class, 'bulkRestore'])->name('exams.bulk-restore');
    Route::patch('exams/bulk-status', [ExamController::class, 'bulkUpdateStatus'])->name('exams.bulk-status');
    Route::patch('exams/{id}/restore', [ExamController::class, 'restore'])->name('exams.restore')->whereNumber('id');
    Route::get('exams/{exam}/attempts/export', [ExamAttemptListController::class, 'export'])->name('exams.attempts.export')->whereNumber('exam');
    Route::get('exams/{exam}/attempts', [ExamAttemptListController::class, 'index'])->name('exams.attempts.index')->whereNumber('exam');
    Route::resource('exams', ExamController::class);
    Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

    // ── Blogs ─────────────────────────────────────────────────────────────────
    Route::prefix('blogs')->name('blogs.')->group(function () {
        Route::post('categories/bulk-destroy', [BlogCategoryController::class, 'bulkDestroyCategories'])->name('categories.bulk-destroy');
        Route::post('categories/bulk-restore', [BlogCategoryController::class, 'bulkRestoreCategories'])->name('categories.bulk-restore');
        Route::patch('categories/bulk-status', [BlogCategoryController::class, 'bulkUpdateCategoryStatus'])->name('categories.bulk-status');
        Route::patch('categories/{id}/restore', [BlogCategoryController::class, 'restoreCategory'])->name('categories.restore')->whereNumber('id');
        Route::resource('categories', BlogCategoryController::class)->names('categories');
        Route::post('bulk-destroy', [BlogController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('bulk-restore', [BlogController::class, 'bulkRestore'])->name('bulk-restore');
        Route::patch('bulk-status', [BlogController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::patch('{blog}/restore', [BlogController::class, 'restore'])->name('restore')->withTrashed();
    });
    Route::resource('blogs', BlogController::class);

    // ── News ──────────────────────────────────────────────────────────────────
    Route::prefix('news')->name('news.')->group(function () {
        Route::post('categories/bulk-destroy', [NewsCategoryController::class, 'bulkDestroyCategories'])->name('categories.bulk-destroy');
        Route::post('categories/bulk-restore', [NewsCategoryController::class, 'bulkRestoreCategories'])->name('categories.bulk-restore');
        Route::patch('categories/bulk-status', [NewsCategoryController::class, 'bulkUpdateCategoryStatus'])->name('categories.bulk-status');
        Route::patch('categories/{id}/restore', [NewsCategoryController::class, 'restoreCategory'])->name('categories.restore')->whereNumber('id');
        Route::resource('categories', NewsCategoryController::class)->names('categories');
        Route::post('bulk-destroy', [NewsController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('bulk-restore', [NewsController::class, 'bulkRestore'])->name('bulk-restore');
        Route::patch('bulk-status', [NewsController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::patch('{news}/restore', [NewsController::class, 'restore'])->name('restore')->withTrashed();
    });
    Route::resource('news', NewsController::class);

    // ── Advertisements ────────────────────────────────────────────────────────
    Route::middleware('admin.capability:organization')->group(function () {
        Route::get('advertisements', [AdvertisementController::class, 'index'])->name('advertisements.index');
        Route::get('advertisements/placements', [AdvertisementController::class, 'placements'])->name('advertisements.placements.index');
        Route::post('advertisements/placements', [AdvertisementController::class, 'storePlacement'])->name('advertisements.placements.store');
        Route::put('advertisements/placements/{placement}', [AdvertisementController::class, 'updatePlacement'])->name('advertisements.placements.update');
        Route::delete('advertisements/placements/{placement}', [AdvertisementController::class, 'destroyPlacement'])->name('advertisements.placements.destroy');
        Route::put('advertisements/custom-code', [AdvertisementController::class, 'updateCustomCode'])->name('advertisements.custom-code');
        Route::post('advertisements/google', [AdvertisementController::class, 'storeGoogle'])->name('advertisements.google.store');
        Route::put('advertisements/google/{googleAdvertisement}', [AdvertisementController::class, 'updateGoogle'])->name('advertisements.google.update');
        Route::delete('advertisements/google/{googleAdvertisement}', [AdvertisementController::class, 'destroyGoogle'])->name('advertisements.google.destroy');
        Route::post('advertisements', [AdvertisementController::class, 'store'])->name('advertisements.store');
        Route::put('advertisements/{advertisement}', [AdvertisementController::class, 'update'])->name('advertisements.update');
        Route::delete('advertisements/{advertisement}', [AdvertisementController::class, 'destroy'])->name('advertisements.destroy');
    });

    // ── Settings ─────────────────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('admin.capability:platform')->group(function () {
            Route::get('/', [CacheOptimizationController::class, 'edit'])->name('index');
            Route::post('cache/run', [CacheOptimizationController::class, 'run'])->name('cache.run');
            Route::get('maintenance', [MaintenanceSettingController::class, 'edit'])->name('maintenance');
            Route::put('maintenance', [MaintenanceSettingController::class, 'update'])->name('maintenance.update');
            Route::get('email', [EmailSettingController::class, 'edit'])->name('email');
            Route::put('email', [EmailSettingController::class, 'update'])->name('email.update');
            Route::post('email/test', [EmailSettingController::class, 'sendTest'])->name('email.test');
            Route::get('integrations', [IntegrationsSettingController::class, 'edit'])->name('integrations');
            Route::put('integrations', [IntegrationsSettingController::class, 'update'])->name('integrations.update');
            Route::get('security', [SecuritySettingController::class, 'edit'])->name('security');
            Route::put('security', [SecuritySettingController::class, 'update'])->name('security.update');
        });

        Route::middleware('admin.capability:organization')->group(function () {
            Route::get('organization', [OrganizationSettingController::class, 'edit'])->name('organization');
            Route::put('organization', [OrganizationSettingController::class, 'update'])->name('organization.update');
            Route::post('organization/heroes', [OrganizationSettingController::class, 'storeHero'])->name('organization.heroes.store');
            Route::put('organization/heroes/{hero}', [OrganizationSettingController::class, 'updateHero'])->name('organization.heroes.update')->whereNumber('hero');
            Route::delete('organization/heroes/{hero}', [OrganizationSettingController::class, 'destroyHero'])->name('organization.heroes.destroy')->whereNumber('hero');
            Route::post('organization/heroes/reorder', [OrganizationSettingController::class, 'reorderHeroes'])->name('organization.heroes.reorder');
            Route::get('organization/faqs', [OrganizationFaqController::class, 'index'])->name('organization.faqs.index');
            Route::post('organization/faqs', [OrganizationFaqController::class, 'store'])->name('organization.faqs.store');
            Route::put('organization/faqs/{faq}', [OrganizationFaqController::class, 'update'])->name('organization.faqs.update')->whereNumber('faq');
            Route::delete('organization/faqs/{faq}', [OrganizationFaqController::class, 'destroy'])->name('organization.faqs.destroy')->whereNumber('faq');
            Route::get('organization/members', [OrganizationMemberController::class, 'index'])->name('organization.members.index');
            Route::post('organization/members', [OrganizationMemberController::class, 'store'])->name('organization.members.store');
            Route::put('organization/members/{member}', [OrganizationMemberController::class, 'update'])->name('organization.members.update')->whereNumber('member');
            Route::delete('organization/members/{member}', [OrganizationMemberController::class, 'destroy'])->name('organization.members.destroy')->whereNumber('member');
            Route::get('seo', [SeoSettingController::class, 'edit'])->name('seo');
            Route::put('seo', [SeoSettingController::class, 'update'])->name('seo.update');
            Route::post('seo/regenerate', [SeoSettingController::class, 'regenerate'])->name('seo.regenerate');
            Route::get('llm', [LlmManagementController::class, 'index'])->name('llm.index');
            Route::post('llm/accounts', [LlmManagementController::class, 'store'])->name('llm.accounts.store');
            Route::put('llm/accounts/{account}', [LlmManagementController::class, 'update'])->name('llm.accounts.update')->whereNumber('account');
            Route::delete('llm/accounts/{account}', [LlmManagementController::class, 'destroy'])->name('llm.accounts.destroy')->whereNumber('account');
            Route::patch('llm/accounts/{account}/toggle-status', [LlmManagementController::class, 'toggleStatus'])->name('llm.accounts.toggle-status')->whereNumber('account');
            Route::patch('llm/accounts/{account}/reset-cooldown', [LlmManagementController::class, 'resetCooldown'])->name('llm.accounts.reset-cooldown')->whereNumber('account');
            Route::post('llm/accounts/{account}/test', [LlmManagementController::class, 'testConnection'])->name('llm.accounts.test')->whereNumber('account');
        });
    });

    // ── Candidates ────────────────────────────────────────────────────────────
    Route::prefix('candidates')->name('candidates.')->group(function () {
        Route::post('bulk-destroy', [CandidateController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('bulk-restore', [CandidateController::class, 'bulkRestore'])->name('bulk-restore');
        Route::patch('bulk-status', [CandidateController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::patch('{candidate}/restore', [CandidateController::class, 'restore'])->name('restore')->whereNumber('candidate');
        Route::patch('{candidate}/toggle-status', [CandidateController::class, 'toggleStatus'])->name('toggle-status')->whereNumber('candidate');
        Route::post('{candidate}/reset-password', [CandidateController::class, 'resetPassword'])->name('reset-password')->whereNumber('candidate');
        Route::get('{candidate}/snapshots/{snapshot}', [CandidateController::class, 'showSnapshot'])->name('snapshots.show')->whereNumber(['candidate', 'snapshot']);
        Route::get('{candidate}/snapshots/{snapshot}/download', [CandidateController::class, 'downloadSnapshot'])->name('snapshots.download')->whereNumber(['candidate', 'snapshot']);
    });
    Route::resource('candidates', CandidateController::class);

    // ── Transactions ──────────────────────────────────────────────────────────
    Route::resource('transactions', TransactionController::class)->only(['index']);

    // ── Read-only resources ───────────────────────────────────────────────────
    Route::resource('notifications', NotificationController::class)->only(['index']);
    Route::resource('logs',          LogController::class)->only(['index']);
});

/*
|--------------------------------------------------------------------------
| Convenience Redirects
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    $user = auth()->user();

    return redirect()->route(
        $user && $user->canAccessAdminPanel()
            ? 'admin.dashboard'
            : 'frontend.account.dashboard'
    );
})->middleware(['auth'])->name('dashboard');

Route::get('/profile', function () {
    $user = auth()->user();

    return redirect()->route(
        $user && $user->canAccessAdminPanel()
            ? 'admin.profile.edit'
            : 'frontend.account.profile'
    );
})->middleware(['auth'])->name('profile.legacy');

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
