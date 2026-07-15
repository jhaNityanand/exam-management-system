@extends('backend.layouts.app')

@section('title', 'Edit Category — ' . $category->name)
@section('page-title', 'Edit Category')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'News', 'url' => route('admin.news.index')],
            ['label' => 'Categories', 'url' => route('admin.news.categories.index')],
            ['label' => $category->name],
        ]" />
@endsection

@section('content')
    <div class="w-full space-y-6">

        {{-- ═══════════════════════════════════════════════════════════════════
        CATEGORY BUILDER CARD
        ════════════════════════════════════════════════════════════════════ --}}
        <x-page-card class="category-builder-card overflow-hidden">
            <form id="category-tree-form" action="{{ route('admin.news.categories.update', $category) }}" method="POST"
                class="category-builder">
                @csrf
                @method('PUT')

                {{-- Hidden field: parent relationship map populated by JS --}}
                <input type="hidden" name="_parent_map" id="parent-map-input" value="{}">

                <div class="category-builder__header">
                    <div>
                        <h1 class="category-builder__title">Edit News Category Hierarchy</h1>
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

                            {{-- Status select --}}
                            <div class="qcat-status-toggle-container">
                                <span class="qcat-status-label">Status</span>
                                <select name="status" id="edit-status-select" class="panel-input text-sm" style="width: auto; min-width: 8rem;">
                                    @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('status', $category->status) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
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
                            @include('backend.news-categories.partials.edit-tree-node', [
                                'node'  => $category,
                                'level' => 0,
                            ])
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                SEO & METADATA SECTION
                ═══════════════════════════════════════════════════════════════ --}}
                @php
                    $seoItem = $category ?? null;
                @endphp
                <div id="metadata-section" class="category-builder__metadata">
                    <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false" tabindex="0">
                        <div class="qcat-meta-header-left">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="qcat-meta-title">SEO &amp; Metadata</span>
                            <span class="qcat-meta-badge">Optional</span>
                        </div>
                        <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div id="meta-accordion-body" class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <p class="qcat-meta-hint mb-4">Add SEO keywords, meta details, and titles to index this content properly.</p>
                        <div class="qcat-seo-container">
                            <!-- Row 1: AI Toggles -->
                            <div class="qcat-seo-row qcat-seo-row--toggles">
                                {{-- Create with AI --}}
                                <div class="qcat-seo-col col-lg-4">
                                    <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                                        <input type="hidden" name="ai_generated" value="0">
                                        <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                                            class="qcat-ai-checkbox" @checked(old('ai_generated', $seoItem?->ai_generated ?? false))>
                                        <span class="qcat-ai-toggle-wrap">
                                            <span class="qcat-ai-thumb"></span>
                                        </span>
                                        <span class="qcat-ai-text">
                                            <span class="qcat-ai-title">Create with AI</span>
                                            <span class="qcat-ai-hint">Let AI generate details automatically</span>
                                        </span>
                                    </label>
                                </div>

                                {{-- Improve with AI --}}
                                <div class="qcat-seo-col col-lg-4" id="improve-with-ai-wrapper">
                                    <label class="qcat-ai-toggle-label" for="toggle-ai-improve">
                                        <input type="hidden" name="ai_improve" value="0">
                                        <input type="checkbox" name="ai_improve" id="toggle-ai-improve" value="1"
                                            class="qcat-ai-checkbox" @checked(old('ai_improve', $seoItem?->ai_improve ?? false))>
                                        <span class="qcat-ai-toggle-wrap">
                                            <span class="qcat-ai-thumb"></span>
                                        </span>
                                        <span class="qcat-ai-text">
                                            <span class="qcat-ai-title">Improve with AI</span>
                                            <span class="qcat-ai-hint">Queue for AI improvement</span>
                                        </span>
                                    </label>
                                </div>

                                {{-- Empty column for layout balance --}}
                                <div class="qcat-seo-col col-lg-4"></div>
                            </div>

                            <!-- Row 2: Focus Keywords, Canonical URL (col-lg-6 each) -->
                            <div class="qcat-seo-row qcat-seo-row--two-cols">
                                {{-- Focus Keywords --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-keywords">Focus Keywords</label>
                                    <input type="text" id="meta-keywords" name="meta_keywords" value="{{ old('meta_keywords', $seoItem?->meta_keywords ?? '') }}" placeholder="e.g. university admission, news items" class="panel-input qcat-meta-input">
                                    @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- Canonical URL --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                                    <input type="url" id="meta-canonical" name="canonical_url" value="{{ old('canonical_url', $seoItem?->canonical_url ?? '') }}" placeholder="https://example.com/canonical" class="panel-input qcat-meta-input">
                                    @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Row 2: Meta Title, Slug, OG Title (col-lg-4 each) -->
                            <div class="qcat-seo-row qcat-seo-row--three-cols">
                                {{-- Meta Title --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                                    <input type="text" id="meta-title" name="meta_title" value="{{ old('meta_title', $seoItem?->meta_title ?? '') }}" placeholder="e.g. University Admissions" class="panel-input qcat-meta-input">
                                    @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- Slug --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-slug">Slug</label>
                                    <input type="text" id="meta-slug" name="slug" value="{{ old('slug', $seoItem?->slug ?? '') }}" placeholder="e.g. slug-value" class="panel-input qcat-meta-input">
                                    @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- OG Title --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                                    <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" placeholder="e.g. Open Graph Title" class="panel-input qcat-meta-input">
                                    @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Row 3: Meta Description, OG Description (col-lg-6 each) -->
                            <div class="qcat-seo-row qcat-seo-row--two-cols">
                                {{-- Meta Description --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                                    <textarea id="meta-desc" name="meta_description" rows="2" placeholder="Brief description for search engines (up to 500 characters)" class="panel-input qcat-meta-textarea">{{ old('meta_description', $seoItem?->meta_description ?? '') }}</textarea>
                                    <span class="qcat-meta-count" data-max="500">0 / 500</span>
                                    @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- OG Description --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                                    <textarea id="meta-og-desc" name="og_description" rows="2" placeholder="Open Graph Description" class="panel-input qcat-meta-textarea">{{ old('og_description', $seoItem?->og_description ?? '') }}</textarea>
                                    @error('og_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="category-builder__footer">
                    <a href="{{ route('admin.news.categories.index') }}" class="panel-button-secondary">
                        Back
                    </a>
                    <button type="submit" class="panel-button-primary" id="save-btn">
                        Update Categories
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
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/question-category-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/backend/category-manager.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/category-form.js') }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
@endpush
