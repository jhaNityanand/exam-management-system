@extends('backend.layouts.app')

@section('title', 'Edit Category — ' . $category->name)
@section('page-title', 'Edit Category')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Questions', 'url' => route('admin.questions.categories.index')],
            ['label' => 'Categories', 'url' => route('admin.questions.categories.index')],
            ['label' => $category->name],
        ]" />
@endsection

@section('content')
    <div class="w-full space-y-6">

        {{-- ═══════════════════════════════════════════════════════════════════
        CATEGORY BUILDER CARD (UI, layout, and styling matches Create page)
        ════════════════════════════════════════════════════════════════════ --}}
        <x-page-card class="category-builder-card overflow-hidden">
            <form id="category-tree-form" action="{{ route('admin.questions.categories.update', $category) }}" method="POST"
                class="category-builder">
                @csrf
                @method('PUT')

                {{-- Hidden field: parent relationship map populated by JS --}}
                <input type="hidden" name="_parent_map" id="parent-map-input" value="{}">

                <div class="category-builder__header">
                    <div>
                        <h1 class="category-builder__title">Edit Category Hierarchy</h1>
                        <p class="category-builder__subtitle">
                            Modify the parent-child structure with nested categories, clear connectors, and room for descriptions.
                        </p>
                    </div>
                </div>

                <div class="category-builder__canvas" id="builder-canvas">
                    <div class="category-builder__intro">
                        <div>
                            <h2>Category Structure</h2>
                            <p>The first row starts as the root category. Add children beneath any row to expand the tree.</p>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                            <div class="category-builder__legend">
                                <span><i></i>Root</span>
                                <span><i></i>Nested levels</span>
                            </div>

                            {{-- Status Toggle Switch --}}
                            <div class="qcat-status-toggle-container">
                                <span class="qcat-status-label">Status</span>
                                <label class="qcat-status-toggle-btn" for="edit-status-toggle">
                                    <input type="hidden" name="status" value="inactive">
                                    <input type="checkbox" id="edit-status-toggle" name="status" value="active"
                                        class="qcat-status-checkbox" @checked(old('status', $category->status) === 'active')>
                                    <span class="qcat-status-toggle-wrap">
                                        <span class="qcat-status-thumb"></span>
                                    </span>
                                </label>
                                <span id="edit-status-indicator-label"
                                    class="qcat-status-text-indicator {{ old('status', $category->status) === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ old('status', $category->status) === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            @error('status')
                                <p class="qcat-field-error is-visible text-right" style="margin-top: 0.1rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="category-tree-scroller">
                        <div id="tree-container" class="category-tree" aria-live="polite">
                            @php
                                $GLOBALS['nodeIndex'] = 0;
                            @endphp
                            @include('backend.question-categories.partials.edit-tree-node', [
                                'node'  => $category,
                                'level' => 0,
                            ])
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                METADATA SECTION
                ════════════════════════════════════════════════════════════════ --}}
                <div id="metadata-section" class="category-builder__metadata">
                    <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false"
                        tabindex="0">
                        <div class="qcat-meta-header-left">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                class="qcat-meta-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="qcat-meta-title">SEO &amp; Metadata</span>
                            <span class="qcat-meta-badge">Optional</span>
                        </div>
                        <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    {{-- SEO & Metadata Toggles --}}
                    <div class="qcat-meta-toggles">
                        <div class="qcat-ai-row">
                            {{-- Create with AI --}}
                            <label class="qcat-ai-toggle-label" for="edit-toggle-ai-create">
                                <input type="hidden" name="ai_generated" value="0">
                                <input type="checkbox" name="ai_generated" id="edit-toggle-ai-create" value="1"
                                    class="qcat-ai-checkbox" @checked(old('ai_generated', $category->ai_generated))>
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
                        class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <p class="qcat-meta-hint">These metadata fields are applied to this category only. Leave blank if
                            not needed.</p>

                        <div class="qcat-meta-grid">
                            {{-- Meta Title --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="edit-meta-title">Meta Title</label>
                                <input type="text" id="edit-meta-title" name="meta_title"
                                    value="{{ old('meta_title', $category->meta_title) }}"
                                    placeholder="e.g. Science — Question Categories" class="panel-input qcat-meta-input">
                                <span class="qcat-meta-count" data-max="255">{{ strlen($category->meta_title ?? '') }} /
                                    255</span>
                                @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Improve with AI --}}
                            <div class="qcat-meta-field qcat-meta-field--ai-improve" id="edit-improve-wrap">
                                <label class="" for="edit-toggle-ai-improve">&nbsp;</label>
                                <label class="qcat-ai-toggle-label" for="edit-toggle-ai-improve">
                                    <input type="hidden" name="ai_improve" value="0">
                                    <input type="checkbox" name="ai_improve" id="edit-toggle-ai-improve" value="1"
                                        class="qcat-ai-checkbox" @checked(old('ai_improve', $category->ai_improve))>
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
                                <span class="qcat-meta-count" data-max="500">{{ strlen($category->meta_description ?? '') }} /
                                    500</span>
                                @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Meta Keywords --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="edit-meta-keywords">Meta Keywords</label>
                                <input type="text" id="edit-meta-keywords" name="meta_keywords"
                                    value="{{ old('meta_keywords', $category->meta_keywords) }}"
                                    placeholder="science, physics, chemistry" class="panel-input qcat-meta-input">
                                @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Slug --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="edit-slug">Slug</label>
                                <input type="text" id="edit-slug" name="slug"
                                    value="{{ old('slug', $category->slug) }}" placeholder="e.g. science-physics"
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
                                    placeholder="Open Graph title for social sharing" class="panel-input qcat-meta-input">
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
    </div>

    {{-- Validation errors from server --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.__serverErrors = @json($errors->all());
            });
        </script>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/backend/category-manager.js') }}"></script>
    <script src="{{ asset('js/backend/question-category-form.js') }}"></script>
@endpush
