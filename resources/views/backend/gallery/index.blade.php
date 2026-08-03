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
<div class="gallery-page" id="gallery-app"
     data-csrf="{{ csrf_token() }}"
     data-endpoints='@json($endpoints)'>
    <section class="panel-card overflow-hidden">
        {{-- Header --}}
        <div class="gallery-header">
            <div class="gallery-header__row">
                <div class="min-w-0">
                    <h2 class="gallery-header__title">Media Gallery</h2>
                    <p class="gallery-header__subtitle">
                        Central library for images and media used across exams and questions.
                    </p>
                </div>
                <div class="gallery-header__actions">
                    <button type="button" id="gallery-refresh" class="gallery-icon-btn" title="Refresh" aria-label="Refresh gallery">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <div class="gallery-segmented" id="gallery-view-toggle" role="group" aria-label="View mode">
                        <button type="button" data-view="grid" class="is-active" title="Grid view" aria-pressed="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button type="button" data-view="list" title="List view" aria-pressed="false">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                    <button type="button" id="gallery-open-size-guide" class="panel-button-secondary gallery-size-guide-trigger" title="Recommended upload sizes">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
                        Size guide
                    </button>
                    <button type="button" id="gallery-open-upload" class="panel-button-primary gallery-upload-trigger">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload
                    </button>
                </div>
            </div>

            {{-- Stats (clickable filters) --}}
            <div class="gallery-stats" id="gallery-stats" role="tablist" aria-label="Filter by type">
                <button type="button" class="gallery-stat is-active" data-stat-filter="all" role="tab" aria-selected="true">
                    <span class="gallery-stat__label">Total</span>
                    <strong data-stat="total">{{ $stats['total'] ?? 0 }}</strong>
                </button>
                <button type="button" class="gallery-stat" data-stat-filter="image" role="tab" aria-selected="false">
                    <span class="gallery-stat__label">Images</span>
                    <strong data-stat="images">{{ $stats['images'] ?? 0 }}</strong>
                </button>
                <button type="button" class="gallery-stat" data-stat-filter="video" role="tab" aria-selected="false">
                    <span class="gallery-stat__label">Videos</span>
                    <strong data-stat="videos">{{ $stats['videos'] ?? 0 }}</strong>
                </button>
                <button type="button" class="gallery-stat" data-stat-filter="document" role="tab" aria-selected="false">
                    <span class="gallery-stat__label">Documents</span>
                    <strong data-stat="documents">{{ $stats['documents'] ?? 0 }}</strong>
                </button>
                <button type="button" class="gallery-stat gallery-stat--bin" data-stat-filter="bin" role="tab" aria-selected="false">
                    <span class="gallery-stat__label">Bin</span>
                    <strong data-stat="bin">{{ $stats['bin'] ?? 0 }}</strong>
                </button>
            </div>

            {{-- Toolbar --}}
            <div class="gallery-toolbar list-toolbar">
                <div class="gallery-toolbar__search list-toolbar__search">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="search" id="gallery-search" class="panel-input w-full pl-9 text-sm" placeholder="Search by name, alt text…">
                </div>
                <div class="gallery-toolbar__filters list-toolbar__controls">
                    <div class="list-toolbar__per-page">
                        <select id="gallery-per-page" class="panel-input per-page-select w-full text-sm" data-disable-search data-placeholder="Select page size">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($n == 24)>{{ $n }} / page</option>
                            @endforeach
                        </select>
                    </div>
                    <x-list-view-tabs
                        id="gallery-trash-toggle"
                        active-label="Library"
                        aria-label="Library or bin"
                    />
                    <button id="btn-toggle-filters" type="button" aria-expanded="false" aria-controls="filter-drawer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Bulk bar --}}
        <div id="gallery-bulk-bar" class="list-bulk-bar gallery-bulk-bar" hidden>
            <div class="list-bulk-bar__inner gallery-bulk-bar__inner">
                <div class="list-bulk-bar__meta">
                    <label class="list-bulk-bar__select-all gallery-bulk-bar__select">
                        <input type="checkbox" id="gallery-select-all" class="list-select-all rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" aria-label="Select all gallery items">
                        <span class="list-bulk-bar__badge" aria-live="polite">
                            <span class="list-bulk-bar__count" id="gallery-selected-count">0</span>
                            <span class="list-bulk-bar__label">selected</span>
                        </span>
                    </label>
                </div>
                <div class="list-bulk-bar__actions">
                    <div class="list-bulk-bar__group" id="gallery-bulk-actions-active">
                        <button type="button" data-bulk="delete" class="list-bulk-btn list-bulk-btn--danger gallery-bulk-btn gallery-bulk-btn--danger">Move to Bin</button>
                    </div>
                    <div class="list-bulk-bar__group" id="gallery-bulk-actions-bin" hidden>
                        <button type="button" data-bulk="restore" class="list-bulk-btn gallery-bulk-btn">Restore</button>
                    </div>
                </div>
                <div class="list-bulk-bar__aside">
                    <button type="button" id="gallery-clear-selection" class="list-bulk-btn list-bulk-btn--ghost gallery-bulk-btn gallery-bulk-btn--ghost" data-list-clear-selection>Clear selection</button>
                </div>
            </div>
        </div>

        {{-- Staged uploads (not yet saved to DB) --}}
        <div id="gallery-pending" class="gallery-pending" hidden>
            <div class="gallery-pending__header">
                <div>
                    <h3 class="gallery-pending__title">Pending uploads</h3>
                    <p class="gallery-pending__subtitle">
                        Review, edit, or remove files before saving. Nothing is stored until you click Save.
                    </p>
                </div>
                <div class="gallery-pending__actions">
                    <button type="button" id="gallery-pending-clear" class="gallery-bulk-btn gallery-bulk-btn--ghost">Clear all</button>
                    <button type="button" id="gallery-pending-save-all" class="panel-button-primary text-sm">Save all</button>
                </div>
            </div>
            <div id="gallery-pending-grid" class="gallery-pending-grid"></div>
        </div>

        {{-- Upload strip --}}
        <div id="gallery-dropzone" class="gallery-dropzone" hidden>
            <input type="file" id="gallery-file-input" class="sr-only" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
            <div class="gallery-dropzone__inner">
                <div class="gallery-dropzone__icon" aria-hidden="true">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
                <div class="gallery-dropzone__copy">
                    <p class="gallery-dropzone__title">Drop files to stage</p>
                    <p class="gallery-dropzone__hint">or <button type="button" id="gallery-browse-btn" class="gallery-link">browse</button> · review before saving</p>
                    <p class="gallery-dropzone__size-hint">See recommended sizes above before uploading (example: 1200 × 630 px for social OG images).</p>
                </div>
                <button type="button" id="gallery-browse-btn-secondary" class="panel-button-primary gallery-dropzone__cta text-sm">Choose files</button>
                <button type="button" id="gallery-close-dropzone" class="gallery-icon-btn gallery-dropzone__close" title="Close" aria-label="Close upload area">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="gallery-content">
            <div id="gallery-grid" class="gallery-grid" data-mode="grid" hidden></div>
            <div id="gallery-skeleton" class="gallery-grid" data-mode="grid" aria-busy="true">
                <x-gallery-skeleton :count="12" />
            </div>
            <div id="gallery-empty" class="gallery-empty" hidden>
                <div class="gallery-empty__drop" id="gallery-empty-drop" tabindex="0" role="button" aria-label="Upload files">
                    <div class="gallery-empty__icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="gallery-empty__title" id="gallery-empty-title">No media found</h3>
                    <p class="gallery-empty__text" id="gallery-empty-text">Upload files to get started. You can review and edit them before saving.</p>
                    <button type="button" class="panel-button-primary gallery-empty__btn" id="gallery-empty-upload">Upload files</button>
                </div>
            </div>
            <div id="gallery-pagination" class="gallery-pagination"></div>
        </div>
    </section>

    {{-- Page-level drag overlay --}}
    <div id="gallery-drag-overlay" class="gallery-drag-overlay" hidden aria-hidden="true">
        <div class="gallery-drag-overlay__card">
            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p>Drop files to stage</p>
        </div>
    </div>

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
                    <div class="mt-4" id="gallery-variant-toggle" hidden>
                        <div class="gallery-segmented gallery-segmented--text w-full" role="group" aria-label="Original or edited">
                            <button type="button" data-preview-variant="modified" class="is-active flex-1">Edited</button>
                            <button type="button" data-preview-variant="original" class="flex-1">Original</button>
                        </div>
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

    <div id="gallery-size-guide-modal" class="gallery-size-guide-modal" hidden aria-hidden="true">
        <div class="gallery-size-guide-modal__backdrop" data-close-size-guide></div>
        <div class="gallery-size-guide-modal__panel" role="dialog" aria-modal="true" aria-labelledby="gallery-size-guide-title">
            <header class="gallery-size-guide-modal__header">
                <div>
                    <h3 id="gallery-size-guide-title" class="gallery-size-guide__title">Recommended upload sizes</h3>
                    <p class="gallery-size-guide__subtitle">Use these sizes so images fit cleanly across branding, content, heroes, ads, and social previews.</p>
                </div>
                <button type="button" class="gallery-icon-btn shrink-0" data-close-size-guide aria-label="Close size guide">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </header>
            <div class="gallery-size-guide-modal__body">
                <div class="gallery-size-guide__groups">
                    @foreach(\App\Support\ImageSizeGuide::grouped() as $group => $sizes)
                        <div class="gallery-size-guide__group">
                            <h4 class="gallery-size-guide__group-title">{{ $group }}</h4>
                            <ul class="gallery-size-guide__list">
                                @foreach($sizes as $size)
                                    <li>
                                        <span class="gallery-size-guide__label">{{ $size['label'] }}</span>
                                        <strong class="gallery-size-guide__dims">{{ $size['width'] }} × {{ $size['height'] }} px</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
            <footer class="gallery-size-guide-modal__footer">
                <button type="button" class="panel-button-primary" data-close-size-guide>Got it</button>
            </footer>
        </div>
    </div>

    @include('backend.partials.image-editor-modal')
</div>

<x-filter-drawer
    title="Filter Gallery"
    subtitle="Narrow media by type and sort order"
>
    <div class="filter-group">
        <label for="gallery-kind" class="filter-label">Media type</label>
        <select id="gallery-kind" name="filters[kind]" class="panel-input w-full text-sm" data-disable-search data-placeholder="Select media type">
            <option value="all">All types</option>
            <option value="image">Images</option>
            <option value="video">Videos</option>
            <option value="document">Documents</option>
            <option value="file">Other</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="gallery-sort" class="filter-label">Sort by</label>
        <select id="gallery-sort" name="filters[sort]" class="panel-input w-full text-sm" data-disable-search data-placeholder="Select sort order">
            <option value="newest">Newest first</option>
            <option value="oldest">Oldest first</option>
            <option value="name_asc">Name A–Z</option>
            <option value="name_desc">Name Z–A</option>
            <option value="size_desc">Largest</option>
            <option value="size_asc">Smallest</option>
        </select>
    </div>
</x-filter-drawer>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/backend/list-ui.css') }}?v={{ filemtime(public_path('css/backend/list-ui.css')) }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/filter-drawer.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ versioned_asset('js/components/filter-drawer.js') }}"></script>
    <script src="{{ asset('js/backend/gallery-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/backend/gallery.js') }}?v={{ time() }}"></script>
@endpush
