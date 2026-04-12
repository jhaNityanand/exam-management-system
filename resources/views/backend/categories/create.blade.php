@extends('backend.layouts.app')

@section('title', 'Create Category Hierarchy')
@section('page-title', 'Create Category Hierarchy')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Categories', 'url' => route('admin.categories.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
    <div class="w-full">
        <x-page-card class="category-builder-card overflow-hidden">
            <form id="category-tree-form" action="#" method="POST" class="category-builder">
                @csrf

                <div class="category-builder__header">
                    <div>
                        <h1 class="category-builder__title">Create Category Hierarchy</h1>
                        <p class="category-builder__subtitle">
                            Build a clean parent-child structure with nested categories, clear connectors, and room for short descriptions.
                        </p>
                    </div>
                </div>

                <div class="category-builder__canvas">
                    <div class="category-builder__intro">
                        <div>
                            <h2>Category Structure</h2>
                            <p>The first row starts as the root category. Add children beneath any row to expand the tree.</p>
                        </div>
                        <div class="category-builder__legend">
                            <span><i></i>Root</span>
                            <span><i></i>Nested levels</span>
                        </div>
                    </div>

                    <div class="category-tree-scroller">
                        <div id="tree-container" class="category-tree" aria-live="polite"></div>
                    </div>
                </div>

                <div class="category-builder__footer">
                    <a href="{{ route('admin.categories.index') }}" class="panel-button-secondary">
                        Back
                    </a>
                    <button type="submit" class="panel-button-primary">
                        Save
                    </button>
                </div>
            </form>
        </x-page-card>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/backend/category.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('js/backend/category.js') }}"></script>
    @endpush
@endsection
