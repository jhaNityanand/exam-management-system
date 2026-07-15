@extends('backend.layouts.app')

@section('title', 'Blogs')
@section('page-title', 'Blog Posts')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Blogs'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Blog Posts</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create, publish, and manage blog content for your organization.</p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <button type="button" id="btn-refresh-blogs" class="blog-refresh-btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800" title="Refresh list" aria-label="Refresh blogs list">
                        <svg class="blog-refresh-btn__icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="{{ route('admin.blogs.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Create Blog</span>
                    </a>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="blog-trash-toggle" role="group" aria-label="Active or bin">
                    <button type="button" data-trash="active" class="is-active">Active</button>
                    <button type="button" data-trash="bin">Bin</button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1 lg:justify-end">
                    <div class="relative w-full sm:w-80">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="search" id="blogs-search" class="panel-input w-full pl-9 text-sm" placeholder="Search blogs…">
                    </div>
                    <select id="blogs-per-page" class="panel-input per-page-select w-full sm:w-32 text-sm">
                        <option value="10" selected>10 / Page</option>
                        <option value="20">20 / Page</option>
                        <option value="50">50 / Page</option>
                        <option value="100">100 / Page</option>
                    </select>
                    <button id="btn-toggle-filters" type="button" class="btn-filters inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 13.707A1 1 0 013 13V4z"/></svg>
                        <span>Filters</span>
                        <span id="blogs-filter-badge" class="filter-badge" aria-hidden="true">0</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="blogs-bulk-bar" class="blog-bulk-bar" hidden>
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input type="checkbox" id="blogs-select-all" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span id="blogs-selected-count">0</span> selected</span>
                </label>
                <div id="blogs-bulk-actions-active" class="flex flex-wrap gap-2">
                    <button type="button" id="btn-bulk-delete" class="blog-bulk-btn blog-bulk-btn--danger">Delete selected</button>
                </div>
                <div id="blogs-bulk-actions-bin" class="flex flex-wrap gap-2" hidden>
                    <button type="button" id="btn-bulk-restore" class="blog-bulk-btn">Restore selected</button>
                </div>
            </div>
        </div>

        <div class="relative overflow-x-auto min-h-[300px]" id="ajax-table-container">
            <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/60 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-2.5 w-10"><span class="sr-only">Select</span></th>
                        <th class="px-3 py-2.5 font-semibold w-14 whitespace-nowrap">
                            <button type="button" class="blog-sort-btn" data-sort-key="id"><span>S.No</span></button>
                        </th>
                        <th class="px-4 py-2.5 font-semibold w-16">Banner</th>
                        <th class="px-4 py-2.5 font-semibold">
                            <button type="button" class="blog-sort-btn" data-sort-key="title"><span>Title</span></button>
                        </th>
                        <th class="px-4 py-2.5 font-semibold">Category</th>
                        <th class="px-4 py-2.5 font-semibold">Author</th>
                        <th class="px-4 py-2.5 font-semibold">Tags</th>
                        <th class="px-4 py-2.5 font-semibold">
                            <button type="button" class="blog-sort-btn" data-sort-key="status"><span>Status</span></button>
                        </th>
                        <th class="px-4 py-2.5 font-semibold">
                            <button type="button" class="blog-sort-btn" data-sort-key="published_at"><span>Published</span></button>
                        </th>
                        <th class="px-4 py-2.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="blogs-table-body" class="divide-y divide-slate-200 dark:divide-slate-800"></tbody>
            </table>

            <div id="blogs-loading" class="hidden table-loading-overlay">
                <svg class="h-8 w-8 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div id="blogs-empty" class="hidden py-12 text-center">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No blog posts found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting filters or create a new post.</p>
                <div class="mt-5">
                    <a href="{{ route('admin.blogs.create') }}" class="panel-button-primary">Create Blog</a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div id="blogs-pagination" class="flex items-center justify-between"></div>
        </div>
    </section>
</div>

{{-- Right-side filter drawer --}}
<div id="filter-drawer" class="offcanvas-drawer" tabindex="-1" aria-labelledby="filter-drawer-title" aria-hidden="true">
    <div class="offcanvas-header">
        <div>
            <h5 class="offcanvas-title" id="filter-drawer-title">Filter Blogs</h5>
            <p class="offcanvas-subtitle">Narrow results by status, category, author, and date</p>
        </div>
        <button type="button" class="offcanvas-close" aria-label="Close filters">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <form id="filter-drawer-form" class="flex flex-col h-full min-h-0 overflow-hidden">
        <div class="offcanvas-body">
            <div class="filter-group">
                <label for="drawer-status-filter" class="filter-label">Status</label>
                <select id="drawer-status-filter" name="filters[status]" class="panel-input w-full text-sm">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="drawer-category-filter" class="filter-label">Categories</label>
                <select id="drawer-category-filter" name="filters[blog_category_id][]" multiple placeholder="All categories">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" data-level="{{ $cat->depth }}" data-category-name="{{ $cat->name }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <p class="filter-hint">Select one or more categories. Nested children of selected parents are included.</p>
            </div>

            <div class="filter-group">
                <label for="drawer-author-filter" class="filter-label">Author</label>
                <select id="drawer-author-filter" name="filters[author_id]" class="w-full text-sm" placeholder="All authors">
                    <option value="">All Authors</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}">{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="drawer-tag-filter" class="filter-label">Tag</label>
                <select id="drawer-tag-filter" name="filters[tag_id]" class="w-full text-sm" placeholder="All tags">
                    <option value="">All Tags</option>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Published date</span>
                <div class="filter-date-grid">
                    <label class="filter-date-field">
                        <span>From</span>
                        <input type="date" name="filters[date_from]" id="drawer-date-from" class="panel-input text-sm">
                    </label>
                    <label class="filter-date-field">
                        <span>To</span>
                        <input type="date" name="filters[date_to]" id="drawer-date-to" class="panel-input text-sm">
                    </label>
                </div>
            </div>

            <input type="hidden" name="filters[trash]" id="drawer-trash-filter" value="active">
        </div>
        <div class="offcanvas-footer">
            <button type="reset" class="panel-button-secondary" id="btn-reset-filters">Reset</button>
            <button type="submit" class="panel-button-primary">Apply Filters</button>
        </div>
    </form>
</div>

<form id="delete-blog-form" action="" method="POST" class="hidden">@csrf @method('DELETE')</form>
<form id="restore-blog-form" action="" method="POST" class="hidden">@csrf @method('PATCH')</form>
<form id="bulk-delete-form" action="{{ route('admin.blogs.bulk-destroy') }}" method="POST" class="hidden">@csrf</form>
<form id="bulk-restore-form" action="{{ route('admin.blogs.bulk-restore') }}" method="POST" class="hidden">@csrf</form>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/blog-list.css') }}?v={{ filemtime(public_path('css/backend/blog-list.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.blogsApiUrl = @json(route('admin.internal-api.blogs-table'));
        window.blogsIndexUrl = @json(route('admin.blogs.index'));
        window.blogsRestoreUrl = @json(url('admin/blogs'));
        window.blogStatusMeta = @json(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'key' => $key]));
        document.addEventListener('DOMContentLoaded', () => {
            window.EmsTomSelectHierarchy?.create('#drawer-category-filter', {
                plugins: ['remove_button'],
                placeholder: 'Select categories…',
                maxItems: null,
                closeAfterSelect: false,
            });

            if (window.TomSelect) {
                new TomSelect('#drawer-author-filter', {
                    allowEmptyOption: true,
                    placeholder: 'All authors',
                    maxOptions: 200,
                });
                new TomSelect('#drawer-tag-filter', {
                    allowEmptyOption: true,
                    placeholder: 'All tags',
                    maxOptions: 300,
                });
            }

            window.EmsTomSelectBlur?.blurNativeSelects(document.getElementById('filter-drawer-form') || document);
        });
    </script>
    <script src="{{ versioned_asset('js/core/dom-utils.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/ajax-table.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/blog-list.js') }}"></script>
@endpush
