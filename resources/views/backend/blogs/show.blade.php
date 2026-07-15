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

        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
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
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">{{ $blog->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    {{ $blog->author_name ?: $blog->author?->name ?: 'Unknown author' }}
                    @if ($blog->published_at)
                        · {{ $blog->published_at->format('M j, Y g:i A') }}
                    @endif
                    · {{ number_format($blog->view_count) }} views
                </p>
                @if ($blog->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-3">
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
        <div class="lg:col-span-8 space-y-6">
            @if ($blog->excerpt)
                <div class="blog-show__excerpt bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-500/10 dark:to-slate-900 border border-indigo-100 dark:border-indigo-500/20 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-indigo-500 uppercase tracking-wider mb-2">Excerpt</h2>
                    <p class="text-slate-700 dark:text-slate-200 text-base leading-relaxed">{{ $blog->excerpt }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-8 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Content</h2>
                <x-rich-text-content :content="$blog->content" class="blog-show__prose prose prose-slate dark:prose-invert max-w-none" />
            </div>

            @if ($blog->galleryAttachments->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Attachments</h2>
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
                </div>
            @endif
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Slug</dt><dd class="font-medium text-slate-900 dark:text-white break-all">{{ $blog->slug }}</dd></div>
                    <div><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $blog->statusLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Banners</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $bannerCollection->count() }}</dd></div>
                    <div><dt class="text-slate-500">Views</dt><dd class="font-medium text-slate-900 dark:text-white">{{ number_format($blog->view_count) }}</dd></div>
                    <div><dt class="text-slate-500">Robots</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $blog->robots ?: 'index,follow' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">SEO Summary</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Meta Title</dt><dd class="text-slate-900 dark:text-white">{{ $blog->seo_title ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Meta Description</dt><dd class="text-slate-700 dark:text-slate-300">{{ $blog->seo_description ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Keywords</dt><dd class="text-slate-700 dark:text-slate-300">{{ $blog->seo_keywords ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Canonical</dt><dd class="text-slate-700 dark:text-slate-300 break-all">{{ $blog->canonical_url ?: '—' }}</dd></div>
                    @if ($blog->ogImage)
                        <div>
                            <dt class="text-slate-500 mb-1">OG Image</dt>
                            <img src="{{ $blog->ogImage->file_url }}" alt="" class="w-full rounded-lg border border-slate-200 dark:border-slate-700">
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/blog-create.css') }}?v={{ filemtime(public_path('css/backend/blog-create.css')) }}">
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
