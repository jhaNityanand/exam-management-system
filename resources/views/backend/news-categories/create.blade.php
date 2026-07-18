@extends('backend.layouts.app')

@section('title', 'Create News Categories')
@section('page-title', 'Create News Categories')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'News', 'url' => route('admin.news.index')],
            ['label' => 'Categories', 'url' => route('admin.news.categories.index')],
            ['label' => 'Create'],
        ]" />
@endsection

@section('content')
    <div class="w-full space-y-6">

        {{-- ═══════════════════════════════════════════════════════════════════
        CATEGORY BUILDER CARD
        ════════════════════════════════════════════════════════════════════ --}}
        <x-page-card class="category-builder-card overflow-hidden">
            <form id="category-tree-form" action="{{ route('admin.news.categories.store') }}" method="POST"
                class="category-builder">
                @csrf

                {{-- Hidden field: parent relationship map populated by JS --}}
                <input type="hidden" name="_parent_map" id="parent-map-input" value="{}">

                <div class="category-builder__header">
                    <div>
                        <h1 class="category-builder__title">Create News Category Hierarchy</h1>
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

                            {{-- Status select --}}
                            <div class="qcat-status-toggle-container">
                                <span class="qcat-status-label">Status</span>
                                <select name="status" id="qcat-status-select" class="panel-input text-sm" style="width: auto; min-width: 8rem;">
                                    @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('status', 'active') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
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

                {{-- SEO & METADATA (shared partial — keep constant across modules) --}}
                @include('backend.partials.seo-metadata-section', [
                    'seoItem' => $category ?? null,
                    'showSlug' => false,
                    'showPublishingExtras' => true,
                    'previewBaseUrl' => url('/categories'),
                    'previewClassPrefix' => 'category',
                ])

                {{-- Footer --}}
                <div class="category-builder__footer">
                    <a href="{{ route('admin.news.categories.index') }}" class="panel-button-secondary">
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
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/question-category-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/backend/category-manager.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/category-form.js') }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
@endpush