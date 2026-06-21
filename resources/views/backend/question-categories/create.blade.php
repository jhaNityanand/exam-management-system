@extends('backend.layouts.app')

@section('title', 'Create Question Categories')
@section('page-title', 'Create Question Categories')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Questions', 'url' => route('admin.questions.categories.index')],
            ['label' => 'Categories', 'url' => route('admin.questions.categories.index')],
            ['label' => 'Create'],
        ]" />
@endsection

@section('content')
    <div class="w-full space-y-6">

        {{-- ═══════════════════════════════════════════════════════════════════
        CATEGORY BUILDER CARD (design preserved exactly as-is)
        ════════════════════════════════════════════════════════════════════ --}}
        <x-page-card class="category-builder-card overflow-hidden">
            <form id="category-tree-form" action="{{ route('admin.questions.categories.store') }}" method="POST"
                class="category-builder">
                @csrf

                {{-- Hidden field: parent relationship map populated by JS --}}
                <input type="hidden" name="_parent_map" id="parent-map-input" value="{}">

                <div class="category-builder__header">
                    <div>
                        <h1 class="category-builder__title">Create Category Hierarchy</h1>
                        <p class="category-builder__subtitle">
                            Build a clean parent-child structure with nested categories, clear connectors, and room for
                            short descriptions.
                        </p>
                    </div>
                </div>

                <div class="category-builder__canvas" id="builder-canvas">
                    <div class="category-builder__intro">
                        <div>
                            <h2>Category Structure</h2>
                            <p>The first row starts as the root category. Add children beneath any row to expand the tree.
                            </p>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                            <div class="category-builder__legend">
                                <span><i></i>Root</span>
                                <span><i></i>Nested levels</span>
                            </div>

                            {{-- Status Toggle Switch --}}
                            <div class="qcat-status-toggle-container">
                                <span class="qcat-status-label">Status</span>
                                <label class="qcat-status-toggle-btn" for="qcat-status-toggle">
                                    <input type="hidden" name="status" value="inactive">
                                    <input type="checkbox" id="qcat-status-toggle" name="status" value="active"
                                        class="qcat-status-checkbox" @checked(old('status', 'active') === 'active')>
                                    <span class="qcat-status-toggle-wrap">
                                        <span class="qcat-status-thumb"></span>
                                    </span>
                                </label>
                                <span id="qcat-status-indicator-label"
                                    class="qcat-status-text-indicator {{ old('status', 'active') === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ old('status', 'active') === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            @error('status')
                                <p class="qcat-field-error is-visible text-right" style="margin-top: 0.1rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="category-tree-scroller">
                        <div id="tree-container" class="category-tree" aria-live="polite"></div>
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
                            <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                                <input type="hidden" name="ai_generated" value="0">
                                <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                                    class="qcat-ai-checkbox" @checked(old('ai_generated'))>
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
                        <p class="qcat-meta-hint">These metadata fields are applied to all categories in the tree. You can
                            leave them blank and fill them individually when editing.</p>

                        <div class="qcat-meta-grid">
                            {{-- Meta Title --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                                <input type="text" id="meta-title" name="meta_title" value="{{ old('meta_title') }}"
                                    placeholder="e.g. Science — Question Categories" class="panel-input qcat-meta-input">
                                <span class="qcat-meta-count" data-max="255">0 / 255</span>
                                @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Improve with AI --}}
                            <div class="qcat-meta-field qcat-meta-field--ai-improve" id="toggle-improve-wrap">
                                <label class="" for="ai-improve">&nbsp;</label>
                                <label class="qcat-ai-toggle-label" for="ai-improve">
                                    <input type="hidden" name="ai_improve" value="0">
                                    <input type="checkbox" name="ai_improve" id="ai-improve" value="1"
                                        class="qcat-ai-checkbox" @checked(old('ai_improve'))>
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
                                <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                                <textarea id="meta-desc" name="meta_description" rows="2"
                                    placeholder="Brief description for search engines (up to 500 characters)"
                                    class="panel-input qcat-meta-textarea">{{ old('meta_description') }}</textarea>
                                <span class="qcat-meta-count" data-max="500">0 / 500</span>
                                @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Meta Keywords --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="meta-keywords">Meta Keywords</label>
                                <input type="text" id="meta-keywords" name="meta_keywords"
                                    value="{{ old('meta_keywords') }}" placeholder="science, physics, chemistry"
                                    class="panel-input qcat-meta-input">
                                @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Slug --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="meta-slug">Slug</label>
                                <input type="text" id="meta-slug" name="slug" value="{{ old('slug') }}"
                                    placeholder="e.g. science-physics" class="panel-input qcat-meta-input">
                                @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Canonical URL --}}
                            <div class="qcat-meta-field qcat-meta-field--full">
                                <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                                <input type="url" id="meta-canonical" name="canonical_url"
                                    value="{{ old('canonical_url') }}" placeholder="https://example.com/categories/science"
                                    class="panel-input qcat-meta-input">
                                @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- OG Title --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                                <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title') }}"
                                    placeholder="Open Graph title for social sharing" class="panel-input qcat-meta-input">
                                @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- OG Description --}}
                            <div class="qcat-meta-field">
                                <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                                <textarea id="meta-og-desc" name="og_description" rows="2"
                                    placeholder="Open Graph description for social sharing"
                                    class="panel-input qcat-meta-textarea">{{ old('og_description') }}</textarea>
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
                    <button type="submit" class="panel-button-primary" id="save-btn">
                        Save Categories
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