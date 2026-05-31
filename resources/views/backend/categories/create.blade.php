@extends('backend.layouts.app')

@section('title', 'Create Category')
@section('page-title', 'Create Category')
@section('content-container-class', 'max-w-3xl')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Categories', 'url' => route('admin.categories.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<x-page-card>
    <form action="{{ route('admin.categories.store') }}" method="POST" id="category-create-form">
        @csrf

        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">New Category</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Create a category to organise questions and exams. Categories can be nested up to 3 levels deep.
            </p>
        </div>

        <div class="px-4 py-6 sm:px-6 space-y-6">

            {{-- ── Name ──────────────────────────────────────────────────────── --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Category Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name') }}"
                       class="panel-input w-full"
                       placeholder="e.g. Mathematics" required>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── Parent Category ───────────────────────────────────────────── --}}
            <div>
                <label for="parent_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Parent Category
                </label>
                <select id="parent_id" name="parent_id" class="panel-input w-full">
                    <option value="">— Root category (no parent) —</option>
                    @foreach ($parents ?? [] as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Leave blank to create a top-level category.
                </p>
                @error('parent_id')
                    <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── Description ───────────────────────────────────────────────── --}}
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Description
                </label>
                <textarea id="description" name="description" rows="4"
                          class="panel-input w-full"
                          placeholder="Describe the purpose of this category…">{{ old('description') }}</textarea>
            </div>

            {{-- ── Status ────────────────────────────────────────────────────── --}}
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select id="status" name="status" class="panel-input w-full">
                    <option value="active"    @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive"  @selected(old('status') === 'inactive')>Inactive</option>
                    <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Inactive categories will not appear as options when creating questions or exams.
                </p>
                @error('status')
                    <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── SEO / Metadata ───────────────────────────────────────────── --}}
            <div class="border-t border-slate-200 dark:border-slate-800 pt-6" x-data="{ open: false }">
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
                                   value="{{ old('meta_title') }}"
                                   maxlength="255" class="panel-input w-full"
                                   placeholder="SEO page title (max 255 chars)">
                        </div>
                        <div>
                            <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug</label>
                            <input type="text" id="slug" name="slug"
                                   value="{{ old('slug') }}"
                                   maxlength="255" class="panel-input w-full"
                                   placeholder="e.g. mathematics">
                        </div>
                    </div>
                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Description</label>
                        <textarea id="meta_description" name="meta_description"
                                  rows="2" maxlength="500" class="panel-input w-full"
                                  placeholder="Brief description for search engines (max 500 chars)">{{ old('meta_description') }}</textarea>
                    </div>
                    <div>
                        <label for="meta_keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Keywords</label>
                        <input type="text" id="meta_keywords" name="meta_keywords"
                               value="{{ old('meta_keywords') }}"
                               maxlength="500" class="panel-input w-full"
                               placeholder="keyword1, keyword2, keyword3">
                    </div>
                    <div>
                        <label for="canonical_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Canonical URL</label>
                        <input type="url" id="canonical_url" name="canonical_url"
                               value="{{ old('canonical_url') }}"
                               class="panel-input w-full"
                               placeholder="https://example.com/categories/...">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="og_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Title</label>
                            <input type="text" id="og_title" name="og_title"
                                   value="{{ old('og_title') }}"
                                   maxlength="255" class="panel-input w-full"
                                   placeholder="Open Graph title">
                        </div>
                        <div>
                            <label for="og_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Description</label>
                            <textarea id="og_description" name="og_description"
                                      rows="2" maxlength="500" class="panel-input w-full"
                                      placeholder="Open Graph description">{{ old('og_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /px-4 py-6 --}}

        <div class="bg-slate-50 px-4 py-4 sm:px-6 dark:bg-slate-900/50 flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-200/80 dark:border-slate-800">
            <a href="{{ route('admin.categories.index') }}" class="panel-button-secondary">Cancel</a>
            <button type="submit" class="panel-button-primary">Save Category</button>
        </div>
    </form>
</x-page-card>
@endsection
