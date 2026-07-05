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

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Category Explorer
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Browse, search, and manage the parent-child question category structure.
                        </p>
                    </div>
                    <div class="shrink-0">
                        <a href="{{ route('admin.questions.categories.create') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Create Category</span>
                        </a>
                    </div>
                </div>

                {{-- Filter / Search Row --}}
                <form id="filter-form" onsubmit="event.preventDefault();"
                      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">

                    <div class="flex flex-col sm:flex-row gap-2 flex-1">

                        {{-- Search --}}
                        <div class="relative w-full md:w-96">
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
                        <select name="status" id="status-filter" class="panel-input w-full sm:w-40">
                            <option value="">All Statuses</option>
                            @foreach (['active', 'inactive', 'suspended'] as $s)
                                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0 sm:justify-end">
                        {{-- Expand All --}}
                        <button id="expand-all-btn" type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                            <svg id="expand-all-icon" class="h-4 w-4 transition-transform duration-300"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span>Expand All</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- ── Tree Body ────────────────────────────────────────────────────── --}}
        <div class="px-4 py-4 sm:px-6 sm:py-6">
            <div id="category-tree-container">
                {{-- Skeleton Loader Placeholder --}}
                <div class="animate-pulse space-y-4">
                    <div class="rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                                    <div class="h-5 w-40 rounded bg-slate-200 dark:bg-slate-800"></div>
                                    <div class="h-5 w-16 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                                </div>
                                <div class="pl-12 space-y-2">
                                    <div class="h-4 w-5/6 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                                    <div class="h-4 w-3/4 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                            </div>
                        </div>
                    </div>
                    <div class="ml-8 rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                                    <div class="h-5 w-32 rounded bg-slate-200 dark:bg-slate-800"></div>
                                </div>
                                <div class="pl-12 space-y-2">
                                    <div class="h-4 w-4/5 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                                    <div class="h-5 w-48 rounded bg-slate-200 dark:bg-slate-800"></div>
                                </div>
                                <div class="pl-12 space-y-2">
                                    <div class="h-4 w-2/3 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                                <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                            </div>
                        </div>
            </div>
        </div>
    </div>
</section>
</div>


{{-- Category Details Modal --}}
<div id="categoryDetailsModal" tabindex="-1" aria-labelledby="categoryDetailsModalLabel" aria-hidden="true"
     class="cat-modal-overlay" role="dialog">
    <div class="cat-modal-dialog max-w-4xl">
        <div class="cat-modal-card">
            <div class="cat-modal-head">
                <div>
                    <p class="category-desc-modal-eyebrow">Category Profile</p>
                    <h5 class="category-desc-modal-title" id="categoryDetailsModalLabel">Category Name</h5>
                </div>
                <button type="button" class="category-desc-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="cat-modal-body p-6 space-y-6">
                <!-- Inner skeleton loader -->
                <div id="modal-skeleton" class="animate-pulse space-y-4">
                    <div class="h-4 w-1/3 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-20 bg-slate-200 dark:bg-slate-800 rounded"></div>
                </div>
                
                <div id="modal-content" class="hidden space-y-6">
                    <!-- Basic Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Slug</span>
                            <span id="modal-slug" class="text-sm font-medium text-slate-800 dark:text-slate-200"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</span>
                            <span id="modal-status-badge"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parent Category</span>
                            <span id="modal-parent" class="text-sm font-medium text-slate-800 dark:text-slate-200"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">AI Integration</span>
                            <span id="modal-ai-flags" class="flex gap-2"></span>
                        </div>
                    </div>
                    
                    <hr class="border-slate-200 dark:border-slate-700" />
                    
                    <!-- Description -->
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description</span>
                        <p id="modal-description" class="text-sm leading-relaxed text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800/80"></p>
                    </div>

                    <hr class="border-slate-200 dark:border-slate-700" />

                    <!-- Child Categories -->
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Child Categories</span>
                        <div id="modal-children" class="flex flex-wrap gap-2 text-sm text-slate-700 dark:text-slate-300"></div>
                    </div>

                    <hr class="border-slate-200 dark:border-slate-700" />

                    <!-- SEO Accordion Section -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                        <button type="button" id="modal-seo-toggle" class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 dark:bg-slate-900 text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>SEO Metadata</span>
                            <svg id="modal-seo-toggle-icon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="modal-seo-content" class="hidden p-4 space-y-4 bg-white dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800 text-sm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Meta Title</span>
                                    <span id="modal-meta-title" class="text-xs text-slate-800 dark:text-slate-200"></span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Canonical URL</span>
                                    <span id="modal-canonical-url" class="text-xs text-slate-800 dark:text-slate-200"></span>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Meta Keywords</span>
                                    <span id="modal-meta-keywords" class="text-xs text-slate-800 dark:text-slate-200"></span>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Meta Description</span>
                                    <p id="modal-meta-description" class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed"></p>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">OG Title</span>
                                    <span id="modal-og-title" class="text-xs text-slate-800 dark:text-slate-200"></span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">OG Description</span>
                                    <span id="modal-og-description" class="text-xs text-slate-800 dark:text-slate-200"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timestamps -->
                    <div class="flex flex-col sm:flex-row justify-between text-xs text-slate-500 dark:text-slate-400 gap-2 bg-slate-50 dark:bg-slate-900/20 p-3 rounded-xl border border-slate-100 dark:border-slate-800/40">
                        <div>
                            <strong>Created:</strong> <span id="modal-created-at"></span>
                        </div>
                        <div>
                            <strong>Last Updated:</strong> <span id="modal-updated-at"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="cat-modal-foot bg-slate-50 dark:bg-slate-900/60 p-4 flex justify-end">
                <button type="button" class="category-desc-modal-btn-close border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold px-5 py-2.5 rounded-xl transition" data-bs-dismiss="modal">Close</button>
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
