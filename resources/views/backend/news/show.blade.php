@extends('backend.layouts.app')

@section('title', $news->title)
@section('page-title', 'News Details')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'News', 'url' => route('admin.news.index')],
        ['label' => \Illuminate\Support\Str::limit($news->title, 40)],
    ]" />
@endsection

@section('content')
@php
    $bannerCollection = $news->banners->isNotEmpty()
        ? $news->banners
        : collect($news->bannerImage ? [$news->bannerImage] : []);
    $summary = $news->short_description ?: $news->seo_description ?: $news->excerpt;
@endphp

<div class="news-show space-y-6">
    <div class="news-show__hero bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        @if ($bannerCollection->isNotEmpty())
            <div class="news-show-carousel" data-news-carousel>
                <div class="news-show-carousel__track">
                    @foreach ($bannerCollection as $index => $banner)
                        <figure class="news-show-carousel__slide {{ $index === 0 ? 'is-active' : '' }}" data-slide="{{ $index }}">
                            <img src="{{ $banner->file_url }}" alt="{{ $banner->original_name ?: $news->title }}">
                        </figure>
                    @endforeach
                </div>
                @if ($bannerCollection->count() > 1)
                    <button type="button" class="news-show-carousel__nav news-show-carousel__nav--prev" data-carousel-prev aria-label="Previous banner">‹</button>
                    <button type="button" class="news-show-carousel__nav news-show-carousel__nav--next" data-carousel-next aria-label="Next banner">›</button>
                    <div class="news-show-carousel__dots" data-carousel-dots>
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
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">#{{ $news->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold news-status-badge news-status-badge--{{ $news->status }}">
                        {{ $news->statusLabel() }}
                    </span>
                    @if ($news->is_breaking)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300">Breaking</span>
                    @endif
                    @if ($news->is_trending)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">Trending</span>
                    @endif
                    @if ($news->is_featured)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">Featured</span>
                    @endif
                    @if ($news->category)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $news->category->name }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white mt-2.5">{{ $news->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>{{ $news->author_name ?: $news->author?->name ?: 'Unknown author' }}</span>
                    @if ($news->published_at)
                        <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                        <span>{{ $news->published_at->format('M j, Y g:i A') }}</span>
                    @endif
                    <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                    <span>{{ number_format($news->view_count) }} views</span>
                </p>
                @if ($news->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-3.5">
                        @foreach ($news->tags as $tag)
                            <span class="news-tag-chip">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.news.edit', $news) }}" class="panel-button-primary">Edit</a>
                <a href="{{ route('admin.news.index') }}" class="panel-button-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-8 space-y-6 min-w-0">
            @if ($summary)
                <section class="news-show__panel news-show__panel--summary">
                    <header class="news-show__panel-header">
                        <span class="news-show__panel-label">Summary</span>
                    </header>
                    <p class="news-show__summary-text">{{ $summary }}</p>
                </section>
            @endif

            @if ($news->featuredImage)
                <figure class="news-show__panel news-show__featured overflow-hidden !p-0">
                    <img src="{{ $news->featuredImage->file_url }}" alt="{{ $news->featuredImage->original_name ?: $news->title }}" class="w-full max-h-80 object-cover">
                </figure>
            @endif

            <section class="news-show__panel news-show__panel--content">
                <header class="news-show__panel-header">
                    <span class="news-show__panel-label">Content</span>
                </header>
                <x-rich-text-content :content="$news->content" class="news-show__prose" />
            </section>

            @if ($news->galleryAttachments->isNotEmpty())
                <section class="news-show__panel">
                    <header class="news-show__panel-header">
                        <span class="news-show__panel-label">Attachments</span>
                        <span class="news-show__panel-meta">{{ $news->galleryAttachments->count() }}</span>
                    </header>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($news->galleryAttachments as $attachment)
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
            <section class="news-show__panel">
                <header class="news-show__panel-header">
                    <span class="news-show__panel-label">Details</span>
                </header>
                <dl class="news-show__meta-list">
                    <div class="news-show__meta-row">
                        <dt>Slug</dt>
                        <dd class="break-all font-mono text-xs sm:text-sm">{{ $news->slug }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold news-status-badge news-status-badge--{{ $news->status }}">
                                {{ $news->statusLabel() }}
                            </span>
                        </dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Visibility</dt>
                        <dd>{{ $news->visibilityLabel() }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Category</dt>
                        <dd>{{ $news->category?->name ?: '—' }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Author</dt>
                        <dd>{{ $news->author_name ?: $news->author?->name ?: '—' }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Published</dt>
                        <dd>{{ $news->published_at?->format('M j, Y g:i A') ?: '—' }}</dd>
                    </div>
                    @if ($news->expires_at)
                        <div class="news-show__meta-row">
                            <dt>Expires</dt>
                            <dd>{{ $news->expires_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    @endif
                    <div class="news-show__meta-row">
                        <dt>Banners</dt>
                        <dd>{{ $bannerCollection->count() }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Views</dt>
                        <dd>{{ number_format($news->view_count) }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Sort Order</dt>
                        <dd>{{ $news->sort_order }}</dd>
                    </div>
                    <div class="news-show__meta-row">
                        <dt>Robots</dt>
                        <dd>{{ $news->robots ?: 'index,follow' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="news-show__panel">
                <header class="news-show__panel-header">
                    <span class="news-show__panel-label">SEO Summary</span>
                </header>
                <dl class="news-show__meta-list">
                    <div class="news-show__meta-row news-show__meta-row--stack">
                        <dt>Meta Title</dt>
                        <dd>{{ $news->seo_title ?: '—' }}</dd>
                    </div>
                    <div class="news-show__meta-row news-show__meta-row--stack">
                        <dt>Meta Description</dt>
                        <dd class="news-show__meta-desc">{{ $news->seo_description ?: '—' }}</dd>
                    </div>
                    <div class="news-show__meta-row news-show__meta-row--stack">
                        <dt>Keywords</dt>
                        <dd>{{ $news->seo_keywords ?: '—' }}</dd>
                    </div>
                    <div class="news-show__meta-row news-show__meta-row--stack">
                        <dt>Canonical</dt>
                        <dd class="break-all">{{ $news->canonical_url ?: '—' }}</dd>
                    </div>
                    @if ($news->ogImage)
                        <div class="news-show__meta-row news-show__meta-row--stack">
                            <dt>OG Image</dt>
                            <dd>
                                <img src="{{ $news->ogImage->file_url }}" alt="" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 mt-1">
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
    <link rel="stylesheet" href="{{ asset('css/backend/news-list.css') }}?v={{ filemtime(public_path('css/backend/news-list.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/news-create.css') }}?v={{ filemtime(public_path('css/backend/news-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
@endpush

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-news-carousel]');
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
