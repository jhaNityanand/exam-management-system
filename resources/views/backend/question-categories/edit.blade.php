@extends('backend.layouts.app')

@section('title', 'Edit Category — ' . $category->name)
@section('page-title', 'Edit Category')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin',      'url' => route('admin.dashboard')],
        ['label' => 'Questions',  'url' => route('admin.questions.categories.index')],
        ['label' => 'Categories', 'url' => route('admin.questions.categories.index')],
        ['label' => $category->name],
    ]" />
@endsection

@section('content')
<div class="w-full space-y-6">

    {{-- ═══════════════════════════════════════════════════════════════════
         MAIN EDIT FORM
    ════════════════════════════════════════════════════════════════════ --}}
    <x-page-card class="category-builder-card overflow-hidden">
        <form action="{{ route('admin.questions.categories.update', $category) }}"
              method="POST"
              class="category-builder"
              id="qcat-edit-form">
            @csrf
            @method('PUT')

            <div class="category-builder__header">
                <div>
                    <h1 class="category-builder__title">Edit Category</h1>
                    <p class="category-builder__subtitle">
                        Update category details, SEO metadata, and AI settings. Changes apply to this category only.
                    </p>
                </div>
                {{-- Status badge --}}
                <span class="qcat-status-badge qcat-status-badge--{{ $category->status }}">
                    {{ ucfirst($category->status) }}
                </span>
            </div>

            <div class="category-builder__canvas" style="padding-bottom: 0.5rem;">
                <div class="qcat-edit-grid">

                    {{-- Name --}}
                    <div class="qcat-edit-field qcat-edit-field--full">
                        <label class="qcat-meta-label" for="edit-name">
                            Category Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="edit-name" name="name"
                               value="{{ old('name', $category->name) }}"
                               placeholder="Enter category name"
                               class="panel-input qcat-meta-input">
                        @error('name')
                            <p class="qcat-field-error is-visible">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parent Category --}}
                    <div class="qcat-edit-field">
                        <label class="qcat-meta-label" for="edit-parent">Parent Category</label>
                        <select id="edit-parent" name="parent_id" class="panel-input">
                            <option value="">— Root category (no parent) —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}"
                                    @selected(old('parent_id', $category->parent_id) == $parent->id)>
                                    {{ $parent->parent_id ? '&nbsp;&nbsp;&nbsp;↳ ' : '' }}{{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="qcat-field-error is-visible">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="qcat-edit-field">
                        <label class="qcat-meta-label" style="margin-bottom: 0.35rem;">Status</label>
                        <div class="qcat-status-toggle-container" style="margin-top: 0.25rem;">
                            <label class="qcat-status-toggle-btn" for="edit-status-toggle">
                                <input type="hidden" name="status" value="inactive">
                                <input type="checkbox" id="edit-status-toggle" name="status" value="active" class="qcat-status-checkbox"
                                    @checked(old('status', $category->status) === 'active')>
                                <span class="qcat-status-toggle-wrap">
                                    <span class="qcat-status-thumb"></span>
                                </span>
                            </label>
                            <span id="edit-status-indicator-label" class="qcat-status-text-indicator {{ old('status', $category->status) === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ old('status', $category->status) === 'active' ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @error('status')
                            <p class="qcat-field-error is-visible" style="margin-top: 0.1rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="qcat-edit-field qcat-edit-field--full">
                        <label class="qcat-meta-label" for="edit-description">Description</label>
                        <textarea id="edit-description" name="description" rows="3"
                                  placeholder="Optional: Add a short description for this category"
                                  class="panel-input qcat-meta-textarea">{{ old('description', $category->description) }}</textarea>
                    </div>

                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 METADATA SECTION
            ════════════════════════════════════════════════════════════════ --}}
            <div id="metadata-section" class="category-builder__metadata">
                <div class="qcat-meta-header" id="meta-accordion-toggle" role="button"
                     aria-expanded="{{ ($category->meta_title || $category->meta_description || $category->meta_keywords || $category->og_title) ? 'true' : 'false' }}"
                     tabindex="0">
                    <div class="qcat-meta-header-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="qcat-meta-title">SEO &amp; Metadata</span>
                        <span class="qcat-meta-badge">Optional</span>
                    </div>
                    <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                {{-- SEO & Metadata Toggles --}}
                <div class="qcat-meta-toggles">
                    <div class="qcat-ai-row">
                        {{-- Create with AI --}}
                        <label class="qcat-ai-toggle-label" for="edit-toggle-ai-create">
                            <input type="hidden"   name="ai_generated" value="0">
                            <input type="checkbox" name="ai_generated" id="edit-toggle-ai-create"
                                   value="1" class="qcat-ai-checkbox"
                                   @checked(old('ai_generated', $category->ai_generated))>
                            <span class="qcat-ai-toggle-wrap">
                                <span class="qcat-ai-thumb"></span>
                            </span>
                            <span class="qcat-ai-text">
                                <span class="qcat-ai-title">Create with AI</span>
                                <span class="qcat-ai-hint">Let AI generate SEO and metadata details automatically</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div id="meta-accordion-body"
                     class="qcat-meta-body border-t border-slate-200/80 dark:border-slate-800 pt-4 {{ ($category->meta_title || $category->meta_description || $category->og_title) ? '' : 'hidden' }}">
                    <p class="qcat-meta-hint">These metadata fields are applied to this category only. Leave blank if not needed.</p>

                    <div class="qcat-meta-grid">
                        {{-- Meta Title --}}
                        <div class="qcat-meta-field">
                            <label class="qcat-meta-label" for="edit-meta-title">Meta Title</label>
                            <input type="text" id="edit-meta-title" name="meta_title"
                                   value="{{ old('meta_title', $category->meta_title) }}"
                                   placeholder="e.g. Science — Question Categories"
                                   class="panel-input qcat-meta-input">
                            <span class="qcat-meta-count" data-max="255">{{ strlen($category->meta_title ?? '') }} / 255</span>
                            @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- Improve with AI --}}
                        <div class="qcat-meta-field qcat-meta-field--ai-improve" id="edit-improve-wrap">
                            <label class="" for="ai-improve">&nbsp;</label>
                            <label class="qcat-ai-toggle-label" for="edit-toggle-ai-improve">
                                <input type="hidden"   name="ai_improve" value="0">
                                <input type="checkbox" name="ai_improve" id="edit-toggle-ai-improve"
                                       value="1" class="qcat-ai-checkbox"
                                       @checked(old('ai_improve', $category->ai_improve))>
                                <span class="qcat-ai-toggle-wrap">
                                    <span class="qcat-ai-thumb"></span>
                                </span>
                                <span class="qcat-ai-text">
                                    <span class="qcat-ai-title">Improve with AI</span>
                                    <span class="qcat-ai-hint">Queue SEO and metadata for AI-based improvement</span>
                                </span>
                            </label>
                        </div>

                        {{-- Meta Description --}}
                        <div class="qcat-meta-field qcat-meta-field--full">
                            <label class="qcat-meta-label" for="edit-meta-desc">Meta Description</label>
                            <textarea id="edit-meta-desc" name="meta_description" rows="2"
                                      placeholder="Brief description for search engines (up to 500 characters)"
                                      class="panel-input qcat-meta-textarea">{{ old('meta_description', $category->meta_description) }}</textarea>
                            <span class="qcat-meta-count" data-max="500">{{ strlen($category->meta_description ?? '') }} / 500</span>
                            @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- Meta Keywords --}}
                        <div class="qcat-meta-field">
                            <label class="qcat-meta-label" for="edit-meta-keywords">Meta Keywords</label>
                            <input type="text" id="edit-meta-keywords" name="meta_keywords"
                                   value="{{ old('meta_keywords', $category->meta_keywords) }}"
                                   placeholder="science, physics, chemistry"
                                   class="panel-input qcat-meta-input">
                            @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- Slug --}}
                        <div class="qcat-meta-field">
                            <label class="qcat-meta-label" for="edit-slug">Slug</label>
                            <input type="text" id="edit-slug" name="slug"
                                   value="{{ old('slug', $category->slug) }}"
                                   placeholder="e.g. science-physics"
                                   class="panel-input qcat-meta-input">
                            @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- Canonical URL --}}
                        <div class="qcat-meta-field qcat-meta-field--full">
                            <label class="qcat-meta-label" for="edit-canonical">Canonical URL</label>
                            <input type="url" id="edit-canonical" name="canonical_url"
                                   value="{{ old('canonical_url', $category->canonical_url) }}"
                                   placeholder="https://example.com/categories/science"
                                   class="panel-input qcat-meta-input">
                            @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- OG Title --}}
                        <div class="qcat-meta-field">
                            <label class="qcat-meta-label" for="edit-og-title">OG Title</label>
                            <input type="text" id="edit-og-title" name="og_title"
                                   value="{{ old('og_title', $category->og_title) }}"
                                   placeholder="Open Graph title for social sharing"
                                   class="panel-input qcat-meta-input">
                            @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>

                        {{-- OG Description --}}
                        <div class="qcat-meta-field">
                            <label class="qcat-meta-label" for="edit-og-desc">OG Description</label>
                            <textarea id="edit-og-desc" name="og_description" rows="2"
                                      placeholder="Open Graph description for social sharing"
                                      class="panel-input qcat-meta-textarea">{{ old('og_description', $category->og_description) }}</textarea>
                            @error('og_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="category-builder__footer">
                <a href="{{ route('admin.questions.categories.index') }}" class="panel-button-secondary">
                    Back
                </a>
                <button type="submit" class="panel-button-primary">
                    Update Category
                </button>
            </div>

        </form>
    </x-page-card>

    {{-- ═══════════════════════════════════════════════════════════════════
         CHILDREN PANEL (shown only if this category has children)
    ════════════════════════════════════════════════════════════════════ --}}
    @if ($children->isNotEmpty())
    <x-page-card class="overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Child Categories</h2>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                Sub-categories that belong to <strong>{{ $category->name }}</strong>. Deleting a parent will also remove its children.
            </p>
        </div>
        <div class="px-4 py-4 sm:px-6">
            <ul class="space-y-3" id="children-list">
                @foreach ($children as $child)
                    @include('backend.question-categories.partials.child-node', ['node' => $child, 'depth' => 0])
                @endforeach
            </ul>
        </div>
    </x-page-card>
    @endif

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-list.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/backend/question-category-form.js') }}"></script>
    <script>
    // Flash toast on redirect back with success
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

        // Children delete buttons
        document.getElementById('children-list')?.addEventListener('click', (e) => {
            const btn = e.target.closest('.child-delete-btn');
            if (!btn) return;
            const name = btn.dataset.name || 'this category';
            const form = document.getElementById('delete-form-' + btn.dataset.id);
            if (!form) return;

            Swal.fire({
                title: 'Delete Category',
                html: `<div class="swal-cat-body">
                    <div class="swal-cat-icon-wrap"><span class="swal-cat-icon-ring">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg></span></div>
                    <p class="swal-cat-message">You are about to permanently delete</p>
                    <div class="swal-cat-name-chip"><span>${name}</span></div>
                    <p class="swal-cat-warning">This action is <strong>irreversible</strong>.</p>
                </div>`,
                showCancelButton: true, reverseButtons: true, focusCancel: true,
                buttonsStyling: false,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'swal-cat-popup', title: 'swal-cat-title',
                    htmlContainer: 'swal-cat-html',
                    confirmButton: 'swal-cat-confirm-btn',
                    cancelButton: 'swal-cat-cancel-btn',
                    actions: 'swal-cat-actions',
                },
            }).then(r => { if (r.isConfirmed) form.submit(); });
        });
    });
    </script>
@endpush
