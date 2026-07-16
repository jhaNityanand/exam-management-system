@extends('backend.layouts.app')

@section('title', 'Questions')
@section('page-title', 'Questions Repository')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Questions'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Questions Bank
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage your repository of questions across all categories and difficulties.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <button type="button"
                            id="btn-refresh-questions"
                            class="q-refresh-btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Refresh list"
                            aria-label="Refresh questions list">
                        <svg class="q-refresh-btn__icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Question</span>
                    </a>
                </div>
            </div>

            <div class="list-toolbar">
                <div class="list-toolbar__search">
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="search" id="questions-search" class="panel-input w-full pl-9 text-sm" placeholder="Search questions...">
                    </div>
                </div>

                <div class="list-toolbar__controls">
                    {{-- Per Page Dropdown --}}
                    <div class="relative w-28 sm:w-32">
                        <select id="questions-per-page" class="panel-input per-page-select w-full text-sm">
                            <option value="10" selected>10 / Page</option>
                            <option value="20">20 / Page</option>
                            <option value="50">50 / Page</option>
                            <option value="100">100 / Page</option>
                        </select>
                    </div>

                    <div class="list-view-tabs" role="tablist" aria-label="Question visibility">
                        <button type="button" role="tab" aria-selected="true" data-trash="active" class="is-active">Active</button>
                        <button type="button" role="tab" aria-selected="false" data-trash="bin">Bin</button>
                    </div>

                    {{-- Filter Button --}}
                    <button id="btn-toggle-filters" type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="questions-bulk-bar" class="list-bulk-bar" hidden>
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><span id="questions-selected-count">0</span> selected</span>
                <div id="questions-bulk-actions-active" class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btn-bulk-delete" class="list-bulk-btn list-bulk-btn--danger">Move to Bin</button>
                    <select id="questions-bulk-status" class="panel-input text-sm w-36" aria-label="New status">
                        <option value="">Update Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div id="questions-bulk-actions-bin" hidden>
                    <button type="button" id="btn-bulk-restore" class="list-bulk-btn">Restore</button>
                </div>
            </div>
        </div>

        <div class="list-table-wrap" id="ajax-table-container">
            <table class="list-table text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="list-table__heading w-10"><input type="checkbox" id="questions-select-all" class="list-select-all" aria-label="Select all questions"></th>
                        <x-list-sort-header key="id" label="S.No" class="w-14" />
                        <x-list-sort-header key="body" label="Question Details" />
                        <x-list-sort-header key="type" label="Type" />
                        <th scope="col" class="px-4 py-2.5 font-semibold">Category</th>
                        <x-list-sort-header key="difficulty" label="Difficulty" />
                        <x-list-sort-header key="marks" label="Marks" />
                        <th scope="col" class="px-4 py-2.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="questions-table-body" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <!-- Loaded via JS -->
                </tbody>
            </table>

            {{-- Loading Spinner overlay --}}
            <div id="questions-loading" class="hidden table-loading-overlay">
                <svg class="h-8 w-8 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            {{-- Empty State Component --}}
            <div id="questions-empty" class="hidden py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No questions found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting your filters or create a new question.</p>
                <div class="mt-5">
                    <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Question</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="questions-pagination" class="flex items-center justify-between">
                <!-- Pagination loaded via JS -->
            </div>
        </div>
    </section>
</div>

{{-- Right-Side Filter Drawer --}}
<div id="filter-drawer" class="offcanvas-drawer" tabindex="-1" aria-labelledby="filter-drawer-title">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filter-drawer-title">Filter Questions</h5>
        <button type="button" class="offcanvas-close" aria-label="Close">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form id="filter-drawer-form" class="flex flex-col h-full overflow-hidden">
        <div class="offcanvas-body">
            {{-- Category Filter --}}
            <div class="filter-group">
                <label for="drawer-category-filter" class="filter-label">Category</label>
                <select id="drawer-category-filter" name="filters[category_id][]" multiple placeholder="All Categories">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}"
                            data-level="{{ $cat->depth }}"
                            data-category-name="{{ $cat->name }}"
                            class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}">
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Select one or more question categories.</p>
            </div>

            {{-- Type Filter --}}
            <div class="filter-group">
                <label for="drawer-type-filter" class="filter-label">Question Type</label>
                <select id="drawer-type-filter" name="filters[type]" class="panel-input w-full text-sm">
                    <option value="">All Types</option>
                    @foreach(\App\Support\ExamFormats::questionTypes() as $type)
                        <option value="{{ $type['id'] }}">{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Difficulty Filter --}}
            <div class="filter-group">
                <label for="drawer-difficulty-filter" class="filter-label">Difficulty</label>
                <select id="drawer-difficulty-filter" name="filters[difficulty]" class="panel-input w-full text-sm">
                    <option value="">All Difficulties</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                    <option value="very_hard">Very Hard</option>
                </select>
            </div>

            {{-- Marks Type Filter --}}
            <div class="filter-group">
                <label for="drawer-marks-type-filter" class="filter-label">Marks Type</label>
                <select id="drawer-marks-type-filter" name="filters[marks_type]" class="panel-input w-full text-sm">
                    <option value="">All Marks Types</option>
                    <option value="single">Single Mark</option>
                    <option value="multiple">Multiple Marks</option>
                </select>
            </div>

            {{-- Marks Options (clickable buttons; single or multi depending on Marks Type) --}}
            <div class="filter-group" id="marks-options-group">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="filter-label !mb-0">Marks</span>
                    <button type="button" id="marks-select-all-btn" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 hidden">
                        Select All
                    </button>
                </div>
                <div id="marks-options-buttons" class="marks-options-grid" role="group" aria-label="Marks options" data-mode="">
                    @for ($i = 1; $i <= 10; $i++)
                        <button type="button"
                                class="marks-option-btn"
                                data-marks="{{ $i }}"
                                aria-pressed="false"
                                disabled>
                            {{ $i }}
                        </button>
                    @endfor
                </div>
                <div id="drawer-marks-filter-values" class="hidden" aria-hidden="true"></div>
                <p id="marks-filter-hint" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Choose a Marks Type above to enable marks filtering.
                </p>
            </div>

            {{-- Status Filter --}}
            <div class="filter-group">
                <label for="drawer-status-filter" class="filter-label">Status</label>
                <select id="drawer-status-filter" name="filters[status]" class="panel-input w-full text-sm">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            {{-- Multi-select MCQ Filter --}}
            <div class="filter-group">
                <label for="drawer-allows-multiple-filter" class="filter-label">Answer Selection</label>
                <select id="drawer-allows-multiple-filter" name="filters[allows_multiple]" class="panel-input w-full text-sm">
                    <option value="">All</option>
                    <option value="0">Single Correct</option>
                    <option value="1">Multiple Correct</option>
                </select>
            </div>
        </div>

        <div class="offcanvas-footer">
            <button type="reset" class="panel-button-secondary">
                Reset
            </button>
            <button type="submit" class="panel-button-primary">
                Apply Filters
            </button>
        </div>
    </form>
</div>

{{-- Delete Form (hidden) --}}
<form id="delete-question-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
<form id="restore-question-form" action="" method="POST" class="hidden">@csrf @method('PATCH')</form>
<form id="bulk-delete-question-form" action="{{ route('admin.questions.bulk-destroy') }}" method="POST" class="hidden">@csrf</form>
<form id="bulk-restore-question-form" action="{{ route('admin.questions.bulk-restore') }}" method="POST" class="hidden">@csrf</form>
<form id="bulk-status-question-form" action="{{ route('admin.questions.bulk-status') }}" method="POST" class="hidden">@csrf @method('PATCH')<input type="hidden" name="status"></form>

@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-list.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/list-ui.css') }}?v={{ filemtime(public_path('css/backend/list-ui.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ time() }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.questionsApiUrl = @json(route('admin.internal-api.questions-table'));
        window.questionsIndexUrl = @json(route('admin.questions.index'));
        window.questionsRestoreUrl = @json(url('/admin/questions'));
        window.questionTypeMeta = @json(
            collect(\App\Support\ExamFormats::questionTypes())->mapWithKeys(
                fn ($type) => [$type['id'] => ['label' => $type['label'], 'class' => $type['badge_class']]]
            )
        );

        document.addEventListener('DOMContentLoaded', function() {
            window.EmsTomSelectHierarchy?.create('#drawer-category-filter', {
                plugins: ['remove_button'],
                placeholder: 'Select categories…',
                maxOptions: null,
                maxItems: null,
                closeAfterSelect: false,
            }) || new TomSelect('#drawer-category-filter', {
                create: false,
                plugins: ['remove_button'],
                placeholder: 'Select categories…',
                maxOptions: null,
                maxItems: null,
                closeAfterSelect: false,
            });
            window.EmsTomSelectBlur?.blurNativeSelects(document.getElementById('filter-drawer-form') || document);
        });
    </script>
    <script src="{{ versioned_asset('js/core/dom-utils.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/ajax-table.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/list-ui.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/question-list.js') }}"></script>
@endpush
