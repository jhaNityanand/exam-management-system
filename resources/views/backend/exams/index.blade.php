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

        {{-- Page header toolbar --}}
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Exam Workspace</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Manage and review all exam configurations.</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.exams.create') }}" id="btn-exam-create"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Create Exam</span>
                    </a>

                    <button type="button" id="btn-open-filter"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        <span>Filters</span>
                        <span id="filter-active-dot" class="hidden h-2 w-2 rounded-full bg-indigo-500"></span>
                    </button>
                </div>
            </div>

            {{-- Search row --}}
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <label class="relative block w-full sm:max-w-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="search" id="exam-list-search" class="panel-input pl-9 w-full" placeholder="Search exam title, category, owner…">
                </label>

                {{-- Active filter chips --}}
                <div id="active-filter-chips" class="hidden flex flex-wrap items-center gap-2"></div>
            </div>
        </div>

        {{-- Stat grid --}}
        <div id="exam-stat-grid" class="grid gap-3 border-b border-slate-200/80 px-4 py-3 sm:grid-cols-2 sm:px-6 xl:grid-cols-4 dark:border-slate-800"></div>

        {{-- Table --}}
        <div class="overflow-x-auto min-h-[320px]">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Exam</th>
                        <th class="px-6 py-4 font-semibold">Schedule</th>
                        <th class="px-6 py-4 font-semibold">Question Setup</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="exam-list-table-body" class="divide-y divide-slate-200 dark:divide-slate-800"></tbody>
            </table>

            <div id="exam-list-loading" class="hidden py-12 text-center">
                <div class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-r-transparent dark:border-slate-700"></span>
                    <span>Loading exam records…</span>
                </div>
            </div>

            <div id="exam-list-empty" class="hidden px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No exams match your filters</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try clearing a filter or adjusting your search keyword.</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="exam-list-pagination" class="flex items-center justify-between"></div>
        </div>
    </section>
</div>

{{-- ============================================================
     Filter Drawer Overlay
     ============================================================ --}}
<div id="filter-overlay" class="exam-drawer-overlay" aria-hidden="true"></div>

<aside id="filter-drawer" class="exam-filter-drawer" role="dialog" aria-modal="true" aria-label="Exam filters">
    <div class="exam-filter-drawer__header">
        <h3 class="exam-filter-drawer__title">
            <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Filter Exams
        </h3>
        <button type="button" id="btn-close-filter" class="exam-filter-drawer__close" aria-label="Close filters">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="exam-filter-drawer__body">
        {{-- Status --}}
        <fieldset class="exam-filter-group">
            <legend class="exam-filter-label">Status</legend>
            <div class="exam-filter-pill-group" id="filter-status-group">
                <label class="exam-filter-pill is-active">
                    <input type="radio" name="filter-status" value="" checked class="sr-only">All
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-status" value="published" class="sr-only">Published
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-status" value="draft" class="sr-only">Draft
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-status" value="active" class="sr-only">Active
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-status" value="inactive" class="sr-only">Inactive
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-status" value="suspended" class="sr-only">Suspended
                </label>
            </div>
        </fieldset>

        {{-- Mode --}}
        <fieldset class="exam-filter-group">
            <legend class="exam-filter-label">Exam Mode</legend>
            <div class="exam-filter-pill-group" id="filter-mode-group">
                <label class="exam-filter-pill is-active">
                    <input type="radio" name="filter-mode" value="" checked class="sr-only">All
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-mode" value="standard" class="sr-only">Standard
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-mode" value="practice" class="sr-only">Practice
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-mode" value="proctored" class="sr-only">Proctored
                </label>
            </div>
        </fieldset>

        {{-- Difficulty --}}
        <fieldset class="exam-filter-group">
            <legend class="exam-filter-label">Difficulty Level</legend>
            <div class="exam-filter-pill-group" id="filter-difficulty-group">
                <label class="exam-filter-pill is-active">
                    <input type="radio" name="filter-difficulty" value="" checked class="sr-only">All
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-difficulty" value="beginner" class="sr-only">Beginner
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-difficulty" value="intermediate" class="sr-only">Intermediate
                </label>
                <label class="exam-filter-pill">
                    <input type="radio" name="filter-difficulty" value="advanced" class="sr-only">Advanced
                </label>
            </div>
        </fieldset>

        {{-- Sort --}}
        <fieldset class="exam-filter-group">
            <legend class="exam-filter-label">Sort By</legend>
            <select id="exam-list-sort" class="panel-input text-sm w-full">
                <option value="updated_at:desc">Recently Updated</option>
                <option value="title:asc">Title A → Z</option>
                <option value="title:desc">Title Z → A</option>
                <option value="duration:desc">Longest Duration</option>
                <option value="question_count:desc">Most Questions</option>
                <option value="pass_percentage:asc">Lowest Pass %</option>
            </select>
        </fieldset>
    </div>

    <div class="exam-filter-drawer__footer">
        <button type="button" id="btn-reset-filters" class="panel-button-secondary flex-1">Reset All</button>
        <button type="button" id="btn-apply-filters" class="panel-button-primary flex-1">Apply Filters</button>
    </div>
</aside>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/exam-list.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.examListConfig = {
            baseUrl: @json(rtrim(route('admin.exams.index'), '/')),
        };
    </script>
    <script src="{{ asset('js/backend/exam-list.js') }}"></script>
@endpush
