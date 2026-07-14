@extends('backend.layouts.app')

@section('title', 'Edit Blog')
@section('page-title', 'Edit Blog')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Blogs', 'url' => route('admin.blogs.index')],
        ['label' => $blog->title],
    ]" />
@endsection

@section('content')
<div class="w-full relative">
    <x-page-card class="category-builder-card overflow-visible relative z-10 w-full">
        <form action="{{ route('admin.blogs.update', $blog) }}" method="POST" id="blog-form" enctype="multipart/form-data" class="category-builder">
            @csrf
            @method('PUT')
            <div class="category-builder__header px-4 py-6 sm:px-6">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Edit Blog Post</h1>
                    <p class="category-builder__subtitle text-slate-500">Update content, metadata, and publishing settings.</p>
                </div>
            </div>

            @include('backend.blogs.partials.form', ['blog' => $blog])

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.blogs.show', $blog) }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Save Changes</button>
            </div>
        </form>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/blog-create.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ time() }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ time() }}"></script>
    @php
        $blogExistingMedia = [
            'banner_image_id' => $blog->bannerImage?->file_url,
            'og_image_id' => $blog->ogImage?->file_url,
            'attachment_ids' => $blog->galleryAttachments
                ->mapWithKeys(static function ($gallery) {
                    return [$gallery->id => $gallery->file_url];
                })
                ->all(),
        ];
    @endphp
    <script>
        window.galleryDataUrl = @json(route('admin.gallery.data'));
        window.galleryStoreUrl = @json(route('admin.gallery.store'));
        window.galleryCsrf = @json(csrf_token());
        window.blogBaseUrl = @json(url('/blog'));
        window.blogExistingMedia = @json($blogExistingMedia);
        document.addEventListener('DOMContentLoaded', () => {
            const catSelect = window.EmsTomSelectHierarchy?.create('#blog_category_id', { placeholder: 'Select category…' });
            window.EmsTomSelectBlur?.attach(catSelect);
            window.EmsTomSelectBlur?.blurNativeSelects(document.getElementById('blog-form') || document);
        });
    </script>
    <script src="{{ asset('js/backend/blog-create.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
@endpush
