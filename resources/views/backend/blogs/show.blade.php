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
<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            @if ($blog->bannerImage)
                <img src="{{ $blog->bannerImage->file_url }}" alt="" class="h-16 w-24 rounded-xl object-cover shrink-0 border border-slate-200 dark:border-slate-700">
            @else
                <div class="h-16 w-24 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 text-slate-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @endif
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">#{{ $blog->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold blog-status-badge blog-status-badge--{{ $blog->status }}">
                        {{ $blog->statusLabel() }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5 truncate">{{ $blog->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $blog->author_name ?: $blog->author?->name ?: 'Unknown author' }}
                    @if ($blog->published_at)
                        · {{ $blog->published_at->format('M j, Y g:i A') }}
                    @endif
                    @if ($blog->category)
                        · {{ $blog->category->name }}
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.blogs.edit', $blog) }}" class="panel-button-primary">Edit</a>
            <a href="{{ route('admin.blogs.index') }}" class="panel-button-secondary">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-8 space-y-6">
            @if ($blog->excerpt)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-2">Excerpt</h2>
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $blog->excerpt }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Content</h2>
                <x-rich-text-content :content="$blog->content" class="prose prose-slate dark:prose-invert max-w-none" />
            </div>

            @if ($blog->galleryAttachments->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Attachments</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($blog->galleryAttachments as $attachment)
                            <a href="{{ $attachment->file_url }}" target="_blank" rel="noopener" class="block rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 hover:ring-2 hover:ring-indigo-500/40 transition">
                                @if ($attachment->kind === 'image')
                                    <img src="{{ $attachment->file_url }}" alt="{{ $attachment->original_name }}" class="w-full h-28 object-cover">
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
                    <div><dt class="text-slate-500">Views</dt><dd class="font-medium text-slate-900 dark:text-white">{{ number_format($blog->view_count) }}</dd></div>
                    <div><dt class="text-slate-500">Robots</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $blog->robots ?: 'index,follow' }}</dd></div>
                </dl>
                @if ($blog->tags->isNotEmpty())
                    <div>
                        <p class="text-slate-500 text-sm mb-2">Tags</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($blog->tags as $tag)
                                <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
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
    <link rel="stylesheet" href="{{ asset('css/backend/blog-create.css') }}">
@endpush
