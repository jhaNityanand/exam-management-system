@extends('backend.layouts.app')

@section('title', $blog->title)
@section('page-title', 'Blog Details')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Blogs', 'url' => route('admin.blogs.index')],
        ['label' => \Illuminate\Support\Str::limit($blog->title, 40)],
    ]" />
@endsection

@section('content')
@php
    $bannerCollection = $blog->banners->isNotEmpty()
        ? $blog->banners
        : collect($blog->bannerImage ? [$blog->bannerImage] : []);
    $summary = $blog->seo_description ?: $blog->excerpt;
@endphp

<div class="blog-show space-y-6">
    <div class="blog-show__hero bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        @if ($bannerCollection->isNotEmpty())
            <div class="blog-show-carousel" data-blog-carousel>
                <div class="blog-show-carousel__track">
                    @foreach ($bannerCollection as $index => $banner)
                        <figure class="blog-show-carousel__slide {{ $index === 0 ? 'is-active' : '' }}" data-slide="{{ $index }}">
                            <img src="{{ $banner->file_url }}" alt="{{ $banner->original_name ?: $blog->title }}">
                        </figure>
                    @endforeach
                </div>
                @if ($bannerCollection->count() > 1)
                    <button type="button" class="blog-show-carousel__nav blog-show-carousel__nav--prev" data-carousel-prev aria-label="Previous banner">‹</button>
                    <button type="button" class="blog-show-carousel__nav blog-show-carousel__nav--next" data-carousel-next aria-label="Next banner">›</button>
                    <div class="blog-show-carousel__dots" data-carousel-dots>
                        @foreach ($bannerCollection as $index => $banner)
                            <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-carousel-dot="{{ $index }}" aria-label="Banner {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="p-5 sm:p-7 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">#{{ $blog->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold blog-status-badge blog-status-badge--{{ $blog->status }}">
                        {{ $blog->statusLabel() }}
                    </span>
                    @if ($blog->category)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $blog->category->name }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white mt-2.5">{{ $blog->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>{{ $blog->author_name ?: $blog->author?->name ?: 'Unknown author' }}</span>
                    @if ($blog->published_at)
                        <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                        <span>{{ $blog->published_at->format('M j, Y g:i A') }}</span>
                    @endif
                    <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                    <span>{{ number_format($blog->view_count) }} views</span>
                </p>
                @if ($blog->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-3.5">
                        @foreach ($blog->tags as $tag)
                            <span class="blog-tag-chip">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.blogs.edit', $blog) }}" class="panel-button-primary">Edit</a>
                <a href="{{ route('admin.blogs.index') }}" class="panel-button-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-8 space-y-6 min-w-0">
            @if ($summary)
                <section class="blog-show__panel blog-show__panel--summary">
                    <header class="blog-show__panel-header">
                        <span class="blog-show__panel-label">Summary</span>
                    </header>
                    <p class="blog-show__summary-text">{{ $summary }}</p>
                </section>
            @endif

            <section class="blog-show__panel blog-show__panel--content">
                <header class="blog-show__panel-header">
                    <span class="blog-show__panel-label">Content</span>
                </header>
                <x-rich-text-content :content="$blog->content" class="blog-show__prose" />
            </section>

            @if ($blog->galleryAttachments->isNotEmpty())
                <section class="blog-show__panel">
                    <header class="blog-show__panel-header">
                        <span class="blog-show__panel-label">Attachments</span>
                        <span class="blog-show__panel-meta">{{ $blog->galleryAttachments->count() }}</span>
                    </header>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($blog->galleryAttachments as $attachment)
                            <a href="{{ $attachment->file_url }}" target="_blank" rel="noopener" class="group block rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 hover:ring-2 hover:ring-indigo-500/40 transition">
                                @if ($attachment->kind === 'image')
                                    <img src="{{ $attachment->file_url }}" alt="{{ $attachment->original_name }}" class="w-full h-28 object-cover group-hover:scale-[1.02] transition">
                                @else
                                    <div class="h-28 flex items-center justify-center bg-slate-50 dark:bg-slate-800 text-sm text-slate-600 dark:text-slate-300 px-2 text-center">{{ $attachment->original_name }}</div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-6">
            <section class="blog-show__panel">
                <header class="blog-show__panel-header">
                    <span class="blog-show__panel-label">Details</span>
                </header>
                <dl class="blog-show__meta-list">
                    <div class="blog-show__meta-row">
                        <dt>Slug</dt>
                        <dd class="break-all font-mono text-xs sm:text-sm">{{ $blog->slug }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold blog-status-badge blog-status-badge--{{ $blog->status }}">
                                {{ $blog->statusLabel() }}
                            </span>
                        </dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Category</dt>
                        <dd>{{ $blog->category?->name ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Author</dt>
                        <dd>{{ $blog->author_name ?: $blog->author?->name ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Published</dt>
                        <dd>{{ $blog->published_at?->format('M j, Y g:i A') ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Banners</dt>
                        <dd>{{ $bannerCollection->count() }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Views</dt>
                        <dd>{{ number_format($blog->view_count) }}</dd>
                    </div>
                    <div class="blog-show__meta-row">
                        <dt>Robots</dt>
                        <dd>{{ $blog->robots ?: 'index,follow' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="blog-show__panel">
                <header class="blog-show__panel-header">
                    <span class="blog-show__panel-label">SEO Summary</span>
                </header>
                <dl class="blog-show__meta-list">
                    <div class="blog-show__meta-row blog-show__meta-row--stack">
                        <dt>Meta Title</dt>
                        <dd>{{ $blog->seo_title ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row blog-show__meta-row--stack">
                        <dt>Meta Description</dt>
                        <dd class="blog-show__meta-desc">{{ $blog->seo_description ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row blog-show__meta-row--stack">
                        <dt>Keywords</dt>
                        <dd>{{ $blog->seo_keywords ?: '—' }}</dd>
                    </div>
                    <div class="blog-show__meta-row blog-show__meta-row--stack">
                        <dt>Canonical</dt>
                        <dd class="break-all">{{ $blog->canonical_url ?: '—' }}</dd>
                    </div>
                    @if ($blog->ogImage)
                        <div class="blog-show__meta-row blog-show__meta-row--stack">
                            <dt>OG Image</dt>
                            <dd>
                                <img src="{{ $blog->ogImage->file_url }}" alt="" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 mt-1">
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/blog-list.css') }}?v={{ filemtime(public_path('css/backend/blog-list.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/blog-create.css') }}?v={{ filemtime(public_path('css/backend/blog-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
@endpush

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-blog-carousel]');
            if (!root) return;
            const slides = [...root.querySelectorAll('[data-slide]')];
            const dots = [...root.querySelectorAll('[data-carousel-dot]')];
            if (slides.length < 2) return;
            let index = 0;
            const show = (next) => {
                index = (next + slides.length) % slides.length;
                slides.forEach((s, i) => s.classList.toggle('is-active', i === index));
                dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
            };
            root.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(index - 1));
            root.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(index + 1));
            dots.forEach((dot) => dot.addEventListener('click', () => show(Number(dot.dataset.carouselDot) || 0)));
        })();
    </script>
@endpush
