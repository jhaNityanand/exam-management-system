@extends('backend.layouts.app')

@section('title', 'Exam Categories')
@section('page-title', 'Exam Category List')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exam Categories'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col gap-4">

                <!-- Header -->
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Exam Category Explorer
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage all top-level exam classifications and group assignments.
                    </p>
                </div>

                <!-- ONE ROW: Search + Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                    <!-- Search (flex grow) -->
                    <div class="relative w-full sm:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <input
                            id="category-search"
                            type="search"
                            placeholder="Search exam categories..."
                            class="panel-input w-full pr-4"
                            style="padding-left: 2.5rem;"
                        >
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span>Filter</span>
                        </button>
                        
                        <a href="{{ route('admin.exam-categories.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Create Exam Category</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-4 sm:px-6 sm:py-6">
            <!-- Dummy Table -->
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 font-medium text-slate-500 dark:text-slate-400">Category Name</th>
                            <th class="px-4 py-3 font-medium text-slate-500 dark:text-slate-400">Description</th>
                            <th class="px-4 py-3 font-medium text-slate-500 dark:text-slate-400">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500 dark:text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">University Admissions</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 truncate max-w-xs">Standardized tests for undergraduate university admissions.</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">Active</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.exam-categories.edit', 1) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10 transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">Corporate Hiring</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 truncate max-w-xs">Assessment exams for tech and management hiring.</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">Active</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.exam-categories.edit', 2) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10 transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">Internal Promotions</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 truncate max-w-xs">Exams designed for evaluating internal staff for promotion cycles.</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">Inactive</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.exam-categories.edit', 3) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10 transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Dummy Pagination -->
            <div class="mt-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Showing 1 to 3 of 3 results</p>
                <div class="flex items-center gap-2">
                    <button class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800" disabled>Previous</button>
                    <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-medium text-white">1</button>
                    <button class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800" disabled>Next</button>
                </div>
            </div>

            <div id="category-empty-state" class="hidden rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40 mt-6">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No exam categories found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Get started by creating a new exam category.</p>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('category-search');
        const rows = document.querySelectorAll('tbody tr');
        const emptyState = document.getElementById('category-empty-state');
        const tableContainer = document.querySelector('.overflow-x-auto');

        if(searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                let visibleCount = 0;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if(text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if(visibleCount === 0) {
                    emptyState.classList.remove('hidden');
                    tableContainer.classList.add('hidden');
                } else {
                    emptyState.classList.add('hidden');
                    tableContainer.classList.remove('hidden');
                }
            });
        }
    });
</script>
@endpush
@endsection
