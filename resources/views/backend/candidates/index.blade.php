@extends('backend.layouts.app')

@section('title', 'Candidates')
@section('page-title', 'Candidates')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Candidates'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Candidates</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Users who have attempted exams in your organization — any role, with attempt counts.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <button type="button"
                            id="btn-refresh-candidates"
                            class="cand-refresh-btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Refresh list"
                            aria-label="Refresh candidates list">
                        <svg class="cand-refresh-btn__icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="{{ route('admin.candidates.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Candidate</span>
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
                        <input type="search" id="candidates-search" class="panel-input w-full pl-9 text-sm" placeholder="Search name, email, username…">
                    </div>
                </div>

                <div class="list-toolbar__controls">
                    <div class="relative w-full sm:w-56 lg:w-64">
                        <select id="candidates-exam-filter" class="panel-input w-full text-sm" aria-label="Filter by exam">
                            <option value="">All exams</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative w-28 sm:w-32">
                        <select id="candidates-per-page" class="panel-input per-page-select w-full text-sm">
                            <option value="10" selected>10 / Page</option>
                            <option value="20">20 / Page</option>
                            <option value="50">50 / Page</option>
                            <option value="100">100 / Page</option>
                        </select>
                    </div>

                    <x-list-view-tabs aria-label="Candidate visibility" />

                    <button id="btn-toggle-filters" type="button" aria-expanded="false" aria-controls="filter-drawer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="candidates-bulk-bar" class="list-bulk-bar" hidden>
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><span id="candidates-selected-count">0</span> selected</span>
                <div id="candidates-bulk-actions-active" class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btn-bulk-delete" class="list-bulk-btn list-bulk-btn--danger">Move to Bin</button>
                    <select id="candidates-bulk-status" class="panel-input text-sm w-36" aria-label="New status">
                        <option value="">Update Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div id="candidates-bulk-actions-bin" hidden>
                    <button type="button" id="btn-bulk-restore" class="list-bulk-btn">Restore</button>
                </div>
            </div>
        </div>

        <div class="list-table-wrap" id="ajax-table-container">
            <table class="list-table text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="list-table__heading w-10"><input type="checkbox" id="candidates-select-all" class="list-select-all" aria-label="Select all candidates"></th>
                        <x-list-sort-header key="id" label="S.No" class="w-14" />
                        <th scope="col" class="px-4 py-2.5 font-semibold">Candidate</th>
                        <x-list-sort-header key="email" label="Email" />
                        <th scope="col" class="px-4 py-2.5 font-semibold">Phone</th>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Role</th>
                        <x-list-sort-header key="attempts_count" label="Attempts" />
                        <x-list-sort-header key="status" label="Status" />
                        <th scope="col" class="px-4 py-2.5 font-semibold">Verified</th>
                        <x-list-sort-header key="created_at" label="Joined" />
                        <th scope="col" class="px-4 py-2.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="candidates-table-body" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <x-ajax-table-skeleton :rows="10" :columns="11" />
                </tbody>
            </table>

            <div id="candidates-loading" class="hidden table-loading-overlay">
                <svg class="h-8 w-8 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div id="candidates-empty" class="hidden py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No exam attempters found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try another exam filter, adjust filters, or wait until users attempt an exam.</p>
                <div class="mt-5">
                    <a href="{{ route('admin.candidates.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Candidate</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="candidates-pagination" class="flex items-center justify-between"></div>
        </div>
    </section>
</div>

<x-filter-drawer
    title="Filter Candidates"
    subtitle="Narrow results by exam, status, email verification, and registration date"
>
    <div class="filter-group">
        <label for="drawer-exam-filter" class="filter-label">Exam</label>
        <select id="drawer-exam-filter" name="filters[exam_id]" class="panel-input w-full text-sm">
            <option value="">All exams</option>
            @foreach ($exams as $exam)
                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Shows every user who attempted the selected exam, regardless of role.</p>
    </div>

    <div class="filter-group">
        <label for="drawer-status-filter" class="filter-label">Status</label>
        <select id="drawer-status-filter" name="filters[status]" class="panel-input w-full text-sm">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="filter-group">
        <label for="drawer-email-verified-filter" class="filter-label">Email Verification</label>
        <select id="drawer-email-verified-filter" name="filters[email_verified]" class="panel-input w-full text-sm">
            <option value="">All</option>
            <option value="1">Verified</option>
            <option value="0">Unverified</option>
        </select>
    </div>

    <x-filter-date-range
        id="drawer-created"
        from-name="filters[created_from]"
        to-name="filters[created_to]"
        label="Registration date"
    />

    <input type="hidden" id="drawer-trash-filter" name="filters[trash]" value="active">
</x-filter-drawer>

<form id="candidates-bulk-destroy-form" action="{{ route('admin.candidates.bulk-destroy') }}" method="POST" class="hidden">@csrf</form>
<form id="candidates-bulk-restore-form" action="{{ route('admin.candidates.bulk-restore') }}" method="POST" class="hidden">@csrf</form>
<form id="candidates-bulk-status-form" action="{{ route('admin.candidates.bulk-status') }}" method="POST" class="hidden">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" id="bulk-status-value" value="">
</form>

<x-coming-soon-modal
    id="invoice-coming-soon-modal"
    size="xl"
    title="Coming Soon"
    message="The Invoice feature is currently under development and will be available in a future release."
/>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/list-ui.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/filter-drawer.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/candidate-list.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/coming-soon-modal.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.candidatesApiUrl = @json(route('admin.internal-api.candidates-table'));
        window.candidatesIndexUrl = @json(url('/admin/candidates'));
        window.candidatesCsrf = @json(csrf_token());
    </script>
    <script src="{{ versioned_asset('js/backend/ajax-table.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/list-ui.js') }}"></script>
    <script src="{{ versioned_asset('js/components/filter-drawer.js') }}"></script>
    <script src="{{ versioned_asset('js/components/user-avatar.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/coming-soon-modal.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/candidate-list.js') }}"></script>
@endpush
