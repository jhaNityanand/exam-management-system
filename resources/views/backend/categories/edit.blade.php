@extends('backend.layouts.app')

@section('title', 'Edit category')
@section('page-title', 'Edit category')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Categories', 'url' => route('admin.categories.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-3xl">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="panel-input">
                @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Parent</label>
                <select name="parent_id" class="panel-input">
                    <option value="">- Main category -</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea id="cat-description" name="description" rows="4" class="panel-input" data-rich-text data-editor-height="200" data-editor-toolbar="undo redo | bold italic | bullist numlist">{{ old('description', $category->description) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                <select name="status" class="panel-input">
                    @foreach (['active', 'inactive', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $category->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            {{-- ── SEO / Metadata ───────────────────────────────────────────── --}}
            <div class="border-t border-slate-200 dark:border-slate-800 pt-5" x-data="{ open: false }">
                <button type="button"
                        @click="open = !open"
                        class="flex w-full items-center justify-between text-left group">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                            SEO &amp; Metadata
                            <span class="ml-2 text-xs font-normal text-slate-400">(optional)</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Improve discoverability with meta tags, slug, and Open Graph fields.
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-slate-400 transition-transform duration-200"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-collapse class="mt-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title"
                                   value="{{ old('meta_title', $category->meta_title) }}"
                                   maxlength="255" class="panel-input w-full" placeholder="SEO page title">
                        </div>
                        <div>
                            <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug</label>
                            <input type="text" id="slug" name="slug"
                                   value="{{ old('slug', $category->slug) }}"
                                   maxlength="255" class="panel-input w-full" placeholder="e.g. mathematics">
                        </div>
                    </div>
                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" rows="2" maxlength="500"
                                  class="panel-input w-full"
                                  placeholder="Brief description for search engines">{{ old('meta_description', $category->meta_description) }}</textarea>
                    </div>
                    <div>
                        <label for="meta_keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Keywords</label>
                        <input type="text" id="meta_keywords" name="meta_keywords"
                               value="{{ old('meta_keywords', $category->meta_keywords) }}"
                               maxlength="500" class="panel-input w-full" placeholder="keyword1, keyword2">
                    </div>
                    <div>
                        <label for="canonical_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Canonical URL</label>
                        <input type="url" id="canonical_url" name="canonical_url"
                               value="{{ old('canonical_url', $category->canonical_url) }}"
                               class="panel-input w-full" placeholder="https://example.com/categories/...">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="og_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Title</label>
                            <input type="text" id="og_title" name="og_title"
                                   value="{{ old('og_title', $category->og_title) }}"
                                   maxlength="255" class="panel-input w-full" placeholder="Open Graph title">
                        </div>
                        <div>
                            <label for="og_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Description</label>
                            <textarea id="og_description" name="og_description" rows="2" maxlength="500"
                                      class="panel-input w-full"
                                      placeholder="Open Graph description">{{ old('og_description', $category->og_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="panel-button-primary">Update</button>
                <a href="{{ route('admin.categories.index') }}" class="panel-button-secondary">Back</a>
            </div>
        </form>
    </x-page-card>
@endsection

