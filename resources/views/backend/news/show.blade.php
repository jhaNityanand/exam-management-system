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

        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
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
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">{{ $news->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    {{ $news->author_name ?: $news->author?->name ?: 'Unknown author' }}
                    @if ($news->published_at)
                        · {{ $news->published_at->format('M j, Y g:i A') }}
                    @endif
                    · {{ number_format($news->view_count) }} views
                </p>
                @if ($news->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-3">
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
        <div class="lg:col-span-8 space-y-6">
            @if ($news->short_description || $news->excerpt)
                <div class="news-show__excerpt bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-500/10 dark:to-slate-900 border border-indigo-100 dark:border-indigo-500/20 rounded-2xl p-6 shadow-sm space-y-3">
                    @if ($news->short_description)
                        <div>
                            <h2 class="text-sm font-semibold text-indigo-500 uppercase tracking-wider mb-2">Short Description</h2>
                            <p class="text-slate-700 dark:text-slate-200 text-base leading-relaxed">{{ $news->short_description }}</p>
                        </div>
                    @endif
                    @if ($news->excerpt)
                        <div>
                            <h2 class="text-sm font-semibold text-indigo-500 uppercase tracking-wider mb-2">Excerpt</h2>
                            <p class="text-slate-700 dark:text-slate-200 text-base leading-relaxed">{{ $news->excerpt }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($news->featuredImage)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <img src="{{ $news->featuredImage->file_url }}" alt="{{ $news->featuredImage->original_name ?: $news->title }}" class="w-full max-h-80 object-cover">
                </div>
            @endif

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-8 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Content</h2>
                <x-rich-text-content :content="$news->content" class="news-show__prose prose prose-slate dark:prose-invert max-w-none" />
            </div>

            @if ($news->galleryAttachments->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Attachments</h2>
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
                </div>
            @endif
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Slug</dt><dd class="font-medium text-slate-900 dark:text-white break-all">{{ $news->slug }}</dd></div>
                    <div><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $news->statusLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Visibility</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $news->visibilityLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Banners</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $bannerCollection->count() }}</dd></div>
                    <div><dt class="text-slate-500">Views</dt><dd class="font-medium text-slate-900 dark:text-white">{{ number_format($news->view_count) }}</dd></div>
                    <div><dt class="text-slate-500">Sort Order</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $news->sort_order }}</dd></div>
                    @if ($news->expires_at)
                        <div><dt class="text-slate-500">Expires</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $news->expires_at->format('M j, Y g:i A') }}</dd></div>
                    @endif
                    <div><dt class="text-slate-500">Robots</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $news->robots ?: 'index,follow' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">SEO Summary</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Meta Title</dt><dd class="text-slate-900 dark:text-white">{{ $news->seo_title ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Meta Description</dt><dd class="text-slate-700 dark:text-slate-300">{{ $news->seo_description ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Keywords</dt><dd class="text-slate-700 dark:text-slate-300">{{ $news->seo_keywords ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Canonical</dt><dd class="text-slate-700 dark:text-slate-300 break-all">{{ $news->canonical_url ?: '—' }}</dd></div>
                    @if ($news->ogImage)
                        <div>
                            <dt class="text-slate-500 mb-1">OG Image</dt>
                            <img src="{{ $news->ogImage->file_url }}" alt="" class="w-full rounded-lg border border-slate-200 dark:border-slate-700">
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/news-create.css') }}?v={{ filemtime(public_path('css/backend/news-create.css')) }}">
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
