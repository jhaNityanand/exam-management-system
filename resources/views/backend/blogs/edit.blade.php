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

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.blogs.show', $blog) }}" class="panel-button-secondary text-center">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Save Changes</button>
            </div>
        </form>
    </x-page-card>

    @include('backend.partials.image-editor-modal')
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/blog-create.css') }}?v={{ filemtime(public_path('css/backend/blog-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ asset('js/backend/gallery-editor.js') }}?v={{ filemtime(public_path('js/backend/gallery-editor.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}"></script>
    <script src="{{ asset('js/components/datetime-picker.js') }}?v={{ filemtime(public_path('js/components/datetime-picker.js')) }}"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ filemtime(public_path('js/components/editor.js')) }}"></script>
    @php
        $blogExistingMedia = [
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
        window.galleryCommitUrl = @json(route('admin.gallery.commit'));
        window.galleryCsrf = @json(csrf_token());
        window.contentFormConfig = {
            formId: 'blog-form',
            categorySelector: '#blog_category_id',
            seoSlugId: 'blog-seo-slug',
            baseUrl: @json(url('/blogs')),
            tagItemClass: 'blog-tag-item',
            module: 'blog',
            resolveUrl: @json(route('admin.slug.resolve')),
            ignoreId: @json($blog->id),
            isCreate: false,
            existingMedia: @json($blogExistingMedia),
        };
    </script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script src="{{ asset('js/backend/blog-banners.js') }}?v={{ filemtime(public_path('js/backend/blog-banners.js')) }}"></script>
    <script src="{{ asset('js/backend/blog-create.js') }}?v={{ filemtime(public_path('js/backend/blog-create.js')) }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
@endpush
