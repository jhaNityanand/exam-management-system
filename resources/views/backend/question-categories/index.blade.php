@extends('backend.layouts.app')

@section('title', 'Question Categories')
@section('page-title', 'Question Category List')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin',     'url' => route('admin.dashboard')],
        ['label' => 'Questions', 'url' => route('admin.questions.categories.index')],
        ['label' => 'Categories'],
    ]" />
@endsection

@section('content')

@php
    $levelColors     = ['#4f46e5', '#0f766e', '#d97706', '#dc2626', '#7c3aed', '#2563eb'];
    $levelSoftColors = ['#e0e7ff', '#dff6f3', '#ffedd5', '#ffe4e6', '#ede9fe', '#dbeafe'];
@endphp

<div class="space-y-6">
    <section class="panel-card overflow-hidden">

        {{-- ── Header: title + search + controls ──────────────────────────── --}}
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col gap-4">

                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Category Explorer
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Browse, search, and manage the parent-child question category structure.
                    </p>
                </div>

                {{-- Filter / Search Row --}}
                <form id="filter-form" method="GET" action="{{ route('admin.questions.categories.index') }}"
                      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">

                    <div class="flex flex-col sm:flex-row gap-2 flex-1">

                        {{-- Search --}}
                        <div class="relative w-full sm:max-w-xs">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input id="category-search" type="search" name="search"
                                   value="{{ $search }}"
                                   placeholder="Search categories…"
                                   class="panel-input w-full"
                                   style="padding-left:2.5rem">
                        </div>

                        {{-- Status filter --}}
                        <select name="status" id="status-filter" class="panel-input w-full sm:w-40"
                                onchange="document.getElementById('filter-form').submit()">
                            <option value="">All Statuses</option>
                            @foreach (['active', 'inactive', 'suspended'] as $s)
                                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>

                        {{-- Sort --}}
                        <select name="sort" id="sort-select" class="panel-input w-full sm:w-44"
                                onchange="document.getElementById('filter-form').submit()">
                            @foreach ([
                                'name_asc'  => 'Name A → Z',
                                'name_desc' => 'Name Z → A',
                                'newest'    => 'Newest First',
                                'oldest'    => 'Oldest First',
                            ] as $val => $label)
                                <option value="{{ $val }}" @selected($sort === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">

                        {{-- Search submit --}}
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            Search
                        </button>

                        {{-- Expand All --}}
                        <button id="expand-all-btn" type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                            <svg id="expand-all-icon" class="h-4 w-4 transition-transform duration-300"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span>Expand All</span>
                        </button>

                        {{-- Create --}}
                        <a href="{{ route('admin.questions.categories.create') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Create Category</span>
                        </a>
                    </div>
                </form>

            </div>
        </div>

        {{-- ── Tree Body ────────────────────────────────────────────────────── --}}
        <div class="px-4 py-4 sm:px-6 sm:py-6">

            @if ($categories->isEmpty())
                {{-- Empty state --}}
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No categories yet</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Get started by creating your first category.
                    </p>
                    <a href="{{ route('admin.questions.categories.create') }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        Create Category
                    </a>
                </div>
            @else

                <ul id="category-tree-root" class="space-y-4">
                    @foreach ($categories as $category)
                        @include('backend.question-categories.partials.tree-node', [
                            'node'            => $category,
                            'level'           => 0,
                            'levelColors'     => $levelColors,
                            'levelSoftColors' => $levelSoftColors,
                        ])
                    @endforeach
                </ul>

                {{-- Empty search state (JS-toggled) --}}
                <div id="category-empty-state" class="hidden rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center mt-4 dark:border-slate-700 dark:bg-slate-900/40">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No matching categories</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try a different keyword.</p>
                </div>

            @endif
        </div>

    </section>
</div>

{{-- Description Modal --}}
<div id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true"
     class="cat-modal-overlay" role="dialog">
    <div class="cat-modal-dialog">
        <div class="cat-modal-card">
            <div class="cat-modal-head">
                <div>
                    <p class="category-desc-modal-eyebrow">Category Details</p>
                    <h5 class="category-desc-modal-title" id="descModalLabel">Category</h5>
                </div>
                <button type="button" class="category-desc-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="cat-modal-body">
                <p id="descModalContent" class="category-desc-modal-text"></p>
            </div>
            <div class="cat-modal-foot">
                <button type="button" class="category-desc-modal-btn-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="category-toast" class="pointer-events-none fixed right-4 top-4 z-[60] hidden translate-y-2 opacity-0 transition duration-200">
    <div class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-medium text-white shadow-xl shadow-slate-900/30 dark:bg-white dark:text-slate-950">
        <span id="category-toast-text">Done.</span>
    </div>
</div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/category-hierarchy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-list.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/backend/category-list.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            Swal.fire({
                toast: true, position: 'top-end',
                icon: 'success', iconColor: '#10b981',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 3200, timerProgressBar: true,
                customClass: {
                    popup: 'swal-cat-toast-popup',
                    title: 'swal-cat-toast-title',
                    timerProgressBar: 'swal-cat-toast-bar',
                },
            });
        @endif

        @if (session('error'))
            Swal.fire({
                toast: true, position: 'top-end',
                icon: 'error', iconColor: '#f43f5e',
                title: @json(session('error')),
                showConfirmButton: false,
                timer: 4000, timerProgressBar: true,
                customClass: {
                    popup: 'swal-cat-toast-popup',
                    title: 'swal-cat-toast-title',
                    timerProgressBar: 'swal-cat-toast-bar',
                },
            });
        @endif
    });
    </script>
@endpush
