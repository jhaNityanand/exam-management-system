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
                <div class="shrink-0">
                    <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Question</span>
                    </a>
                </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="search" id="questions-search" class="panel-input w-full pl-9 text-sm" placeholder="Search questions...">
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-64">
                        <select id="questions-category-filter" class="w-full text-sm">
                            <option value="">All Categories</option>
                            <option value="1" class="font-semibold text-slate-900">Science</option>
                            <option value="2">&nbsp;&nbsp;&nbsp;&nbsp;Physics</option>
                            <option value="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Classical Mechanics</option>
                            <option value="4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Quantum Physics</option>
                            <option value="6">&nbsp;&nbsp;&nbsp;&nbsp;Biology</option>
                            <option value="8">&nbsp;&nbsp;&nbsp;&nbsp;Chemistry</option>
                            <option value="9" class="font-semibold text-slate-900">Mathematics</option>
                            <option value="10">&nbsp;&nbsp;&nbsp;&nbsp;Algebra</option>
                            <option value="11">&nbsp;&nbsp;&nbsp;&nbsp;Geometry</option>
                            <option value="12">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Trigonometry</option>
                            <option value="14" class="font-semibold text-slate-900">Computer Science</option>
                            <option value="15">&nbsp;&nbsp;&nbsp;&nbsp;Programming</option>
                            <option value="16">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Web Development</option>
                            <option value="17">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data Structures</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span id="questions-total-count">0</span> items
                </div>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/40 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Question Details</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Type</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Category</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Marks</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="questions-table-body" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <!-- Loaded via JS -->
                </tbody>
            </table>

            <div id="questions-loading" class="hidden flex justify-center items-center py-12">
                <svg class="h-8 w-8 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div id="questions-empty" class="hidden py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No questions found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting your filters or create a new question.</p>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="questions-pagination" class="flex items-center justify-between">
                <!-- Pagination loaded via JS -->
            </div>
        </div>
    </section>
</div>

{{-- Delete Form (hidden) --}}
<form id="delete-question-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/question-list.css') }}">
    <style>
        .ts-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            min-height: 2.5rem;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .dark .ts-control {
            border-color: #334155;
            background-color: #0f172a;
            color: #f8fafc;
        }
        .dark .ts-dropdown, .dark .ts-dropdown .option {
            background-color: #0f172a;
            color: #f8fafc;
        }
        .dark .ts-dropdown .option.active {
            background-color: #1e293b;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.questionsApiUrl = @json(route('admin.internal-api.questions-table'));
        window.questionsIndexUrl = @json(route('admin.questions.index'));

        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#questions-category-filter', {
                create: false,
                placeholder: "All Categories",
                maxOptions: null
            });
        });
    </script>
    <script src="{{ asset('js/backend/question-list.js') }}"></script>
@endpush
