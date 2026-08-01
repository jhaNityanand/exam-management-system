@extends('backend.layouts.app')

@section('title', 'Exam Attempts')
@section('page-title', 'Exam Attempts')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exams', 'url' => route('admin.exams.index')],
        ['label' => \Illuminate\Support\Str::limit($exam->title, 40), 'url' => route('admin.exams.show', $exam)],
        ['label' => 'Attempts'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Exam Attempts</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950 dark:text-white truncate" title="{{ $exam->title }}">{{ $exam->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Candidates who attempted this exam — expand a row to see every attempt.
                    </p>
                </div>
                <div class="shrink-0 flex flex-wrap items-center gap-2">
                    <button type="button"
                            id="btn-refresh-attempts"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Refresh list"
                            aria-label="Refresh attempts list">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="{{ route('admin.exams.attempts.export', $exam) }}" class="panel-button-secondary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                        </svg>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.exams.show', $exam) }}" class="panel-button-secondary">Exam Details</a>
                    <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Back to List</a>
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
                        <input type="search" id="attempts-search" class="panel-input w-full pl-9 text-sm" placeholder="Search name, email, phone…">
                    </div>
                </div>

                <div class="list-toolbar__controls">
                    <div class="list-toolbar__per-page">
                        <select id="attempts-per-page" class="panel-input per-page-select w-full text-sm" data-disable-search data-placeholder="Select page size">
                            <option value="10" selected>10 / Page</option>
                            <option value="15">15 / Page</option>
                            <option value="25">25 / Page</option>
                            <option value="50">50 / Page</option>
                        </select>
                    </div>

                    <button id="btn-toggle-filters" type="button" aria-expanded="false" aria-controls="filter-drawer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="list-table-wrap ea-page-table-wrap" id="ajax-table-container">
            <table class="list-table ea-page-table text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="px-3 py-2.5 font-semibold w-10"></th>
                        <th scope="col" class="px-4 py-2.5 font-semibold ea-th-candidate">Candidate</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Attempts</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Status</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Total Qs</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Attempted</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Right</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Wrong</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Unanswered</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Total Marks</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Neg. Marks</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Scored</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">%</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Result</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Start</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">End</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Duration</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Exam Duration</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Verification</th>
                    </tr>
                </thead>
                <tbody id="attempts-table-body">
                    @for ($i = 0; $i < 6; $i++)
                        <tr class="ajax-table-skeleton-row">
                            <td colspan="19" class="px-4 py-3">
                                <div class="h-10 rounded-lg bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <div id="attempts-loading" class="hidden px-4 py-8 text-center text-sm text-slate-500">Loading…</div>
            <div id="attempts-empty" class="hidden px-4 py-12 text-center">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No candidates found</p>
                <p class="mt-1 text-sm text-slate-500">No one has attempted this exam, or no results match your filters.</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-200/80 px-4 py-3 sm:px-6 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400" id="attempts-total-count"></p>
            <div id="attempts-pagination"></div>
        </div>
    </section>
</div>

{{-- Verification preview (lightweight) --}}
<div id="ea-verify-preview-modal" class="cand-modal" hidden>
    <div class="cand-modal__backdrop" data-ea-preview-close></div>
    <div class="cand-modal__panel cand-modal__panel--media" role="dialog" aria-modal="true">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h3 id="ea-preview-title" class="text-base font-bold text-slate-900 dark:text-white truncate">Preview</h3>
            <button type="button" class="panel-button-secondary" data-ea-preview-close>Close</button>
        </div>
        <img id="ea-preview-image" src="" alt="" class="cand-modal__image">
    </div>
</div>

<x-filter-drawer title="Filter Attempts" subtitle="Narrow candidates by latest attempt status and verification.">
    <div class="space-y-4">
        <div class="filter-group">
            <label for="drawer-status" class="filter-label">Status</label>
            <select id="drawer-status" name="filters[status]" class="panel-input w-full text-sm" data-placeholder="Select status">
                <option value="">All statuses</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
                <option value="completed">Completed</option>
                <option value="in_progress">In Progress</option>
                <option value="auto_submitted">Auto Submitted</option>
                <option value="abandoned">Abandoned</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="drawer-result" class="filter-label">Result</label>
            <select id="drawer-result" name="filters[result]" class="panel-input w-full text-sm" data-placeholder="Select result">
                <option value="">All results</option>
                <option value="passed">Pass</option>
                <option value="failed">Fail</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="drawer-email-verified" class="filter-label">Email verification</label>
            <select id="drawer-email-verified" name="filters[email_verified]" class="panel-input w-full text-sm" data-placeholder="Select verification">
                <option value="">All</option>
                <option value="1">Verified</option>
                <option value="0">Unverified</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="drawer-sort" class="filter-label">Sort By</label>
            <select id="drawer-sort" name="sort" class="panel-input w-full text-sm" data-placeholder="Select sort order">
                <option value="last_attempt_at:desc" selected>Latest attempt</option>
                <option value="last_attempt_at:asc">Oldest attempt</option>
                <option value="attempts_count:desc">Most attempts</option>
                <option value="name:asc">Name A → Z</option>
                <option value="name:desc">Name Z → A</option>
            </select>
        </div>
    </div>
</x-filter-drawer>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/list-ui.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/filter-drawer.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/candidate-list.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/candidate-show.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/exam-attempts-page.css') }}">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/components/filter-drawer.js') }}"></script>
    <script>
        window.examAttemptsPage = {
            apiUrl: @json(route('admin.internal-api.exam-attempters', $exam)),
            exportUrl: @json(route('admin.exams.attempts.export', $exam)),
            examId: {{ (int) $exam->id }},
            examTitle: @json($exam->title),
        };
    </script>
    <script src="{{ versioned_asset('js/core/dom-utils.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/ajax-table.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/list-ui.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/exam-attempts-page.js') }}"></script>
@endpush
