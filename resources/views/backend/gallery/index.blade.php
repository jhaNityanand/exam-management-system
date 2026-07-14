@extends('backend.layouts.app')

@section('title', 'Gallery')
@section('page-title', 'Media Gallery')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Gallery'],
    ]" />
@endsection

@section('content')
<div class="gallery-page space-y-6" id="gallery-app"
     data-csrf="{{ csrf_token() }}"
     data-endpoints='@json($endpoints)'>
    {{-- Header --}}
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Media Gallery</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Central library for images and media used across exams and questions.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="gallery-refresh" class="gallery-icon-btn" title="Refresh" aria-label="Refresh gallery">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <div class="gallery-view-toggle" role="group" aria-label="View mode">
                        <button type="button" data-view="grid" class="is-active" title="Grid view">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button type="button" data-view="list" title="List view">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                    <button type="button" id="gallery-open-upload" class="panel-button-primary inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5" id="gallery-stats">
                <div class="gallery-stat"><span class="gallery-stat__label">Total</span><strong data-stat="total">{{ $stats['total'] ?? 0 }}</strong></div>
                <div class="gallery-stat"><span class="gallery-stat__label">Images</span><strong data-stat="images">{{ $stats['images'] ?? 0 }}</strong></div>
                <div class="gallery-stat"><span class="gallery-stat__label">Videos</span><strong data-stat="videos">{{ $stats['videos'] ?? 0 }}</strong></div>
                <div class="gallery-stat"><span class="gallery-stat__label">Documents</span><strong data-stat="documents">{{ $stats['documents'] ?? 0 }}</strong></div>
                <div class="gallery-stat"><span class="gallery-stat__label">Bin</span><strong data-stat="bin">{{ $stats['bin'] ?? 0 }}</strong></div>
            </div>

            {{-- Filters --}}
            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative w-full lg:max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="search" id="gallery-search" class="panel-input w-full pl-9 text-sm" placeholder="Search by name, alt text…">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select id="gallery-kind" class="panel-input text-sm w-auto min-w-[8rem]">
                        <option value="all">All types</option>
                        <option value="image">Images</option>
                        <option value="video">Videos</option>
                        <option value="document">Documents</option>
                        <option value="file">Other</option>
                    </select>
                    <select id="gallery-sort" class="panel-input text-sm w-auto min-w-[9rem]">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="name_asc">Name A–Z</option>
                        <option value="name_desc">Name Z–A</option>
                        <option value="size_desc">Largest</option>
                        <option value="size_asc">Smallest</option>
                    </select>
                    <select id="gallery-per-page" class="panel-input text-sm w-auto min-w-[7rem]">
                        @foreach ($perPageOptions as $n)
                            <option value="{{ $n }}" @selected($n == 24)>{{ $n }} / page</option>
                        @endforeach
                    </select>
                    <div class="gallery-trash-toggle" role="group" aria-label="Library or bin">
                        <button type="button" data-trash="active" class="is-active">Library</button>
                        <button type="button" data-trash="bin">Bin</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk bar --}}
        <div id="gallery-bulk-bar" class="gallery-bulk-bar" hidden>
            <div class="flex flex-wrap items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input type="checkbox" id="gallery-select-all" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span id="gallery-selected-count">0</span> selected</span>
                </label>
                <div class="flex flex-wrap gap-2" id="gallery-bulk-actions-active">
                    <button type="button" data-bulk="delete" class="gallery-bulk-btn gallery-bulk-btn--danger">Move to bin</button>
                </div>
                <div class="flex flex-wrap gap-2" id="gallery-bulk-actions-bin" hidden>
                    <button type="button" data-bulk="restore" class="gallery-bulk-btn">Restore</button>
                    <button type="button" data-bulk="force" class="gallery-bulk-btn gallery-bulk-btn--danger">Delete forever</button>
                </div>
            </div>
        </div>

        {{-- Dropzone (inline) --}}
        <div id="gallery-dropzone" class="gallery-dropzone" hidden>
            <input type="file" id="gallery-file-input" class="sr-only" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
            <div class="gallery-dropzone__inner">
                <svg class="h-10 w-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Drag & drop files here</p>
                <p class="mt-1 text-xs text-slate-500">or <button type="button" id="gallery-browse-btn" class="text-indigo-600 hover:underline dark:text-indigo-400">browse</button> to upload</p>
                <div id="gallery-upload-progress" class="gallery-upload-progress" hidden>
                    <div class="gallery-upload-progress__bar" id="gallery-upload-progress-bar"></div>
                    <span id="gallery-upload-progress-label">Uploading…</span>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-4 sm:p-6">
            <div id="gallery-grid" class="gallery-grid" data-view="grid"></div>
            <div id="gallery-skeleton" class="gallery-grid" hidden></div>
            <div id="gallery-empty" class="gallery-empty" hidden>
                <svg class="h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h3 class="mt-3 text-base font-semibold text-slate-800 dark:text-slate-100">No media yet</h3>
                <p class="mt-1 text-sm text-slate-500">Upload images or files to build your central media library.</p>
                <button type="button" class="panel-button-primary mt-4" id="gallery-empty-upload">Upload files</button>
            </div>
            <div id="gallery-pagination" class="gallery-pagination"></div>
        </div>
    </section>

    {{-- Preview modal --}}
    <div id="gallery-preview-modal" class="gallery-modal" hidden aria-hidden="true">
        <div class="gallery-modal__backdrop" data-close-modal></div>
        <div class="gallery-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="gallery-preview-title">
            <div class="gallery-modal__layout">
                <div class="gallery-modal__media">
                    <button type="button" class="gallery-modal__nav gallery-modal__nav--prev" id="gallery-preview-prev" aria-label="Previous">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="gallery-modal__nav gallery-modal__nav--next" id="gallery-preview-next" aria-label="Next">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <img id="gallery-preview-image" alt="" class="gallery-modal__image">
                    <div id="gallery-preview-broken" class="gallery-modal__broken" hidden>
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm font-medium">Preview unavailable</p>
                        <p class="text-xs text-slate-500">The file could not be loaded from storage.</p>
                    </div>
                    <div id="gallery-preview-file" class="gallery-modal__file" hidden></div>
                </div>
                <aside class="gallery-modal__meta">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 id="gallery-preview-title" class="text-lg font-semibold text-slate-900 dark:text-white truncate"></h3>
                            <p id="gallery-preview-sub" class="text-xs text-slate-500 mt-1"></p>
                        </div>
                        <button type="button" class="gallery-icon-btn shrink-0" data-close-modal aria-label="Close">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <dl class="gallery-meta-list mt-4">
                        <div><dt>Resolution</dt><dd id="meta-dimensions">—</dd></div>
                        <div><dt>File size</dt><dd id="meta-size">—</dd></div>
                        <div><dt>MIME type</dt><dd id="meta-mime">—</dd></div>
                        <div><dt>Uploaded</dt><dd id="meta-date">—</dd></div>
                        <div class="gallery-meta-list__full"><dt>Path</dt><dd id="meta-path" class="font-mono text-xs break-all">—</dd></div>
                        <div class="gallery-meta-list__full"><dt>URL</dt><dd id="meta-url" class="font-mono text-xs break-all">—</dd></div>
                    </dl>
                    <div class="mt-5 grid grid-cols-2 gap-2" id="gallery-preview-actions"></div>
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ time() }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/backend/gallery.js') }}?v={{ time() }}"></script>
@endpush
