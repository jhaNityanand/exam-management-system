@extends('backend.layouts.app')

@section('title', 'Exam Library')
@section('page-title', 'Exam Library')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exams'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Exam Workspace
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage and review all exam configurations.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <button type="button"
                            id="btn-refresh-exams"
                            class="q-refresh-btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Refresh list"
                            aria-label="Refresh exams list">
                        <svg class="q-refresh-btn__icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="{{ route('admin.exams.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Create Exam</span>
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
                        <input type="search" id="exams-search" class="panel-input w-full pl-9 text-sm" placeholder="Search exams, category, owner…">
                    </div>
                </div>

                <div class="list-toolbar__controls">
                    <div class="list-toolbar__per-page">
                        <select id="exams-per-page" class="panel-input per-page-select w-full text-sm" data-disable-search data-placeholder="Select page size">
                            <option value="10" selected>10 / Page</option>
                            <option value="20">20 / Page</option>
                            <option value="50">50 / Page</option>
                            <option value="100">100 / Page</option>
                        </select>
                    </div>

                    <x-list-view-tabs aria-label="Exam visibility" />

                    <button id="btn-toggle-filters" type="button" aria-expanded="false" aria-controls="filter-drawer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>
            </div>

            {{-- Active filter chips --}}
            <div id="active-filter-chips" class="mt-3 hidden flex flex-wrap items-center gap-2"></div>
        </div>

        {{-- Stat grid (filled via AJAX) --}}
        <div id="exam-stat-grid" class="grid gap-3 border-b border-slate-200/80 px-4 py-3 sm:grid-cols-2 sm:px-6 xl:grid-cols-4 dark:border-slate-800" aria-busy="true">
            @for ($i = 0; $i < 4; $i++)
                <div class="ajax-stat-skeleton__card" aria-hidden="true">
                    <div class="ajax-skeleton-bar" style="width: 45%"></div>
                    <div class="ajax-skeleton-bar" style="width: 30%; height: 1.25rem;"></div>
                </div>
            @endfor
        </div>

        <div id="exams-bulk-bar" class="list-bulk-bar" hidden>
            <div class="list-bulk-bar__inner">
                <div class="list-bulk-bar__meta">
                    <span class="list-bulk-bar__badge" aria-live="polite">
                        <span class="list-bulk-bar__count" id="exams-selected-count">0</span>
                        <span class="list-bulk-bar__label">selected</span>
                    </span>
                </div>
                <div class="list-bulk-bar__actions">
                    <div id="exams-bulk-actions-active" class="list-bulk-bar__group">
                        <button type="button" id="btn-bulk-delete" class="list-bulk-btn list-bulk-btn--danger">Move to Bin</button>
                        <select id="exams-bulk-status" class="panel-input text-sm" data-no-search aria-label="New status">
                            <option value="">Update Status</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div id="exams-bulk-actions-bin" class="list-bulk-bar__group" hidden>
                        <button type="button" id="btn-bulk-restore" class="list-bulk-btn">Restore</button>
                    </div>
                </div>
                <div class="list-bulk-bar__aside">
                    <button type="button" class="list-bulk-btn list-bulk-btn--ghost" data-list-clear-selection>Clear selection</button>
                </div>
            </div>
        </div>

        <div class="list-table-wrap" id="ajax-table-container">
            <table class="list-table text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/40 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="list-table__heading w-10"><input type="checkbox" id="exams-select-all" class="list-select-all" aria-label="Select all exams"></th>
                        <x-list-sort-header key="title" label="Exam" />
                        <x-list-sort-header key="status" label="Status" />
                        <x-list-sort-header key="parts_count" label="Parts" />
                        <x-list-sort-header key="questions_count" label="Questions" />
                        <x-list-sort-header key="total_marks" label="Marks" />
                        <x-list-sort-header key="duration" label="Duration" />
                        <x-list-sort-header key="scheduled_start" label="Schedule" />
                        <th scope="col" class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="exams-table-body" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <x-ajax-table-skeleton :rows="10" :columns="9" />
                </tbody>
            </table>

            <div id="exams-loading" class="hidden table-loading-overlay">
                <svg class="h-8 w-8 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div id="exams-empty" class="hidden py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h3 id="exams-empty-title" class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No exams found</h3>
                <p id="exams-empty-copy" class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting your filters or create a new exam.</p>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    <button type="button" id="exams-empty-clear" class="hidden inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Clear filters
                    </button>
                    <a href="{{ route('admin.exams.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Create Exam</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="exams-pagination" class="flex items-center justify-between">
                {{-- Pagination loaded via JS --}}
            </div>
        </div>
    </section>
</div>

{{-- Right-Side Filter Drawer --}}
<x-filter-drawer
    title="Filter Exams"
    subtitle="Filter by identity, parts structure, scoring, schedule, and sorting"
>
            <div class="filter-section">
                <h4 class="filter-section__title">Identity</h4>
                <div class="filter-group">
                    <label for="drawer-category-filter" class="filter-label">Category</label>
                    <select id="drawer-category-filter" name="filters[category_id][]" multiple data-filter-multiple data-filter-hierarchy="1" data-placeholder="Select categories">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                data-level="{{ $cat->depth }}"
                                data-category-name="{{ $cat->name }}"
                                class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}">
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="drawer-status-filter" class="filter-label">Status</label>
                    <select id="drawer-status-filter" name="filters[status][]" multiple data-filter-multiple data-placeholder="Select statuses">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="drawer-mode-filter" class="filter-label">Exam Mode</label>
                    <select id="drawer-mode-filter" name="filters[exam_mode][]" multiple data-filter-multiple data-placeholder="Select exam modes">
                        <option value="standard">Standard</option>
                        <option value="practice">Practice</option>
                        <option value="proctored">Proctored</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="drawer-format-filter" class="filter-label">Exam Format</label>
                    <select id="drawer-format-filter" name="filters[exam_format][]" multiple data-filter-multiple data-placeholder="Select exam formats">
                        @foreach (\App\Support\ExamFormOptions::formatLabels() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="drawer-difficulty-filter" class="filter-label">Difficulty</label>
                    <select id="drawer-difficulty-filter" name="filters[difficulty_level][]" multiple data-filter-multiple data-placeholder="Select difficulties">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="drawer-visibility-filter" class="filter-label">Visibility</label>
                    <select id="drawer-visibility-filter" name="filters[visibility][]" multiple data-filter-multiple data-placeholder="Select visibility">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                        <option value="invite_only">Invite Only</option>
                    </select>
                </div>
            </div>

            <div class="filter-section">
                <h4 class="filter-section__title">Structure / Parts</h4>
                <div class="filter-group grid grid-cols-2 gap-3">
                    <div>
                        <label for="drawer-parts-min" class="filter-label">Parts Min</label>
                        <input id="drawer-parts-min" type="number" min="0" name="filters[parts_min]" class="panel-input w-full text-sm" placeholder="Min">
                    </div>
                    <div>
                        <label for="drawer-parts-max" class="filter-label">Parts Max</label>
                        <input id="drawer-parts-max" type="number" min="0" name="filters[parts_max]" class="panel-input w-full text-sm" placeholder="Max">
                    </div>
                </div>
                <div class="filter-group grid grid-cols-2 gap-3">
                    <div>
                        <label for="drawer-questions-min" class="filter-label">Questions Min</label>
                        <input id="drawer-questions-min" type="number" min="0" name="filters[questions_min]" class="panel-input w-full text-sm" placeholder="Min">
                    </div>
                    <div>
                        <label for="drawer-questions-max" class="filter-label">Questions Max</label>
                        <input id="drawer-questions-max" type="number" min="0" name="filters[questions_max]" class="panel-input w-full text-sm" placeholder="Max">
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <h4 class="filter-section__title">Scoring</h4>
                <div class="filter-group grid grid-cols-2 gap-3">
                    <div>
                        <label for="drawer-marks-min" class="filter-label">Marks Min</label>
                        <input id="drawer-marks-min" type="number" min="0" name="filters[marks_min]" class="panel-input w-full text-sm" placeholder="Min">
                    </div>
                    <div>
                        <label for="drawer-marks-max" class="filter-label">Marks Max</label>
                        <input id="drawer-marks-max" type="number" min="0" name="filters[marks_max]" class="panel-input w-full text-sm" placeholder="Max">
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <h4 class="filter-section__title">Schedule</h4>
                <div class="filter-group grid grid-cols-2 gap-3">
                    <div>
                        <label for="drawer-duration-min" class="filter-label">Duration Min</label>
                        <input id="drawer-duration-min" type="number" min="1" name="filters[duration_min]" class="panel-input w-full text-sm" placeholder="Min">
                    </div>
                    <div>
                        <label for="drawer-duration-max" class="filter-label">Duration Max</label>
                        <input id="drawer-duration-max" type="number" min="1" name="filters[duration_max]" class="panel-input w-full text-sm" placeholder="Max">
                    </div>
                </div>
                <x-filter-date-range
                    id="drawer-created"
                    label="Created date"
                    from-name="filters[created_from]"
                    to-name="filters[created_to]"
                />
            </div>

            <div class="filter-section">
                <h4 class="filter-section__title">Meta</h4>
                <div class="filter-group">
                    <label for="drawer-sort" class="filter-label">Sort By</label>
                    <select id="drawer-sort" name="sort" class="panel-input w-full text-sm" data-placeholder="Select sort order">
                        <option value="updated_at:desc" selected>Recently Updated</option>
                        <option value="title:asc">Title A â†’ Z</option>
                        <option value="title:desc">Title Z â†’ A</option>
                        <option value="parts_count:desc">Most Parts</option>
                        <option value="questions_count:desc">Most Questions</option>
                        <option value="total_marks:desc">Highest Marks</option>
                        <option value="duration:desc">Longest Duration</option>
                        <option value="pass_percentage:asc">Lowest Pass %</option>
                        <option value="scheduled_start:asc">Earliest Schedule</option>
                    </select>
                </div>
            </div>
</x-filter-drawer>

<form id="delete-exam-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
<form id="restore-exam-form" action="" method="POST" class="hidden">@csrf @method('PATCH')</form>
<form id="bulk-delete-exam-form" action="{{ route('admin.exams.bulk-destroy') }}" method="POST" class="hidden">@csrf</form>
<form id="bulk-restore-exam-form" action="{{ route('admin.exams.bulk-restore') }}" method="POST" class="hidden">@csrf</form>
<form id="bulk-status-exam-form" action="{{ route('admin.exams.bulk-status') }}" method="POST" class="hidden">@csrf @method('PATCH')<input type="hidden" name="status"></form>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/question-list.css') }}?v={{ filemtime(public_path('css/backend/question-list.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-list.css') }}?v={{ filemtime(public_path('css/backend/exam-list.css')) }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/filter-drawer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/list-ui.css') }}?v={{ filemtime(public_path('css/backend/list-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/components/datetime-picker.js') }}?v={{ filemtime(public_path('js/components/datetime-picker.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}?v={{ filemtime(public_path('js/components/tom-select-blur.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ filemtime(public_path('js/components/tom-select-hierarchy.js')) }}"></script>
    <script src="{{ versioned_asset('js/components/filter-drawer.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.examsApiUrl = @json(route('admin.internal-api.exams-table'));
        window.examsIndexUrl = @json(route('admin.exams.index'));
        window.examsRestoreUrl = @json(url('/admin/exams'));
    </script>
    <script src="{{ versioned_asset('js/core/dom-utils.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/ajax-table.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/list-ui.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/exam-list.js') }}"></script>
@endpush
