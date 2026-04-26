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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Exam Workspace
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Review, filter, and manage exam configurations with mock data before wiring final API behavior.
                    </p>
                </div>
                @orgCan('exam.create')
                    <a href="{{ route('admin.exams.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Create Exam</span>
                    </a>
                @endorgCan
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-[1.7fr,1fr,1fr,1fr]">
                <label class="relative block">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="search" id="exam-list-search" class="panel-input pl-9" placeholder="Search exam title, category, or owner...">
                </label>

                <select id="exam-list-status" class="panel-input text-sm">
                    <option value="">All Statuses</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select id="exam-list-mode" class="panel-input text-sm">
                    <option value="">All Modes</option>
                    <option value="standard">Standard</option>
                    <option value="practice">Practice</option>
                    <option value="proctored">Proctored</option>
                </select>

                <select id="exam-list-sort" class="panel-input text-sm">
                    <option value="updated_at:desc">Recently Updated</option>
                    <option value="title:asc">Title A-Z</option>
                    <option value="duration:desc">Longest Duration</option>
                    <option value="question_count:desc">Most Questions</option>
                </select>
            </div>

            <div id="exam-stat-grid" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"></div>
        </div>

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
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-r-transparent dark:border-slate-700 dark:border-r-transparent"></span>
                    <span>Loading exam records...</span>
                </div>
            </div>

            <div id="exam-list-empty" class="hidden px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No exams match your filters</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try clearing one filter or adjusting your search keyword.</p>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="exam-list-pagination" class="flex items-center justify-between"></div>
        </div>
    </section>
</div>
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

