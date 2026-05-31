@extends('backend.layouts.app')

@section('title', 'Create Question')
@section('page-title', 'Create Question')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Questions', 'url' => route('admin.questions.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<div class="w-full relative">
    <x-page-card class="category-builder-card overflow-visible relative z-10 w-full">
        <form action="{{ route('admin.questions.store') }}" method="POST" id="question-form" enctype="multipart/form-data" class="category-builder">
            @csrf

            <div class="category-builder__header px-4 py-6 sm:px-6">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Create Question</h1>
                    <p class="category-builder__subtitle text-slate-500 dark:text-slate-400">
                        Add a new question to the repository with options, answers, and explanations.
                    </p>
                </div>
            </div>

            <div class="px-4 py-5 sm:p-6 space-y-8">

                {{-- ── Top Row: Category, Type, Difficulty, Marks ─────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category_id" name="category_id" class="panel-input mt-1 block w-full">
                            <option value="">Search or select…</option>
                            @foreach ($categories ?? [] as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-category_id">Category is required.</p>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Question Type <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type" class="panel-input mt-1 block w-full">
                            <option value="mcq"          @selected(old('type', 'mcq') === 'mcq')>Multiple Choice</option>
                            <option value="true_false"   @selected(old('type') === 'true_false')>True / False</option>
                            <option value="short_answer" @selected(old('type') === 'short_answer')>Text Answer</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Difficulty <span class="text-red-500">*</span>
                        </label>
                        <select id="difficulty" name="difficulty" class="panel-input mt-1 block w-full">
                            <option value="easy"   @selected(old('difficulty') === 'easy')>Easy</option>
                            <option value="medium" @selected(old('difficulty', 'medium') === 'medium')>Medium</option>
                            <option value="hard"   @selected(old('difficulty') === 'hard')>Hard</option>
                        </select>
                        @error('difficulty')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="marks" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Marks <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="marks" name="marks" value="{{ old('marks', 1) }}"
                               min="1" max="100" class="panel-input mt-1 block w-full">
                        @error('marks')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-marks">Marks are required.</p>
                    </div>
                </div>

                {{-- ── Status & Previously Asked ───────────────────────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:max-w-xs">
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="panel-input mt-1 block w-full">
                            <option value="active"   @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="previous_exam" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Previously Asked In
                        </label>
                        <input type="text" id="previous_exam" name="previous_exam"
                               value="{{ old('previous_exam') }}"
                               class="panel-input mt-1 block w-full"
                               placeholder="e.g. UPSC Prelims 2023">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Optionally note the exam name / year this question appeared in.
                        </p>
                    </div>
                </div>

                {{-- ── Question Body ────────────────────────────────────────────── --}}
                <div class="w-full relative z-0 pt-2">
                    <label for="body" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Question Content <span class="text-red-500">*</span>
                    </label>
                    <textarea id="body" name="body" class="w-full hidden">{{ old('body') }}</textarea>
                    <div id="editor-body" class="ck-editor-container"></div>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-body">Question content cannot be empty.</p>
                </div>

                {{-- ── Dynamic Answer Sections ──────────────────────────────────── --}}
                <div id="dynamic-sections" class="w-full min-h-[300px] mt-4">

                    {{-- MCQ --}}
                    <div id="section-mcq" class="space-y-4 question-type-section">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">
                                Options <span class="text-red-500">*</span>
                            </h4>
                            <label class="flex items-center cursor-pointer gap-2">
                                <input type="checkbox" id="allows_multiple" name="allows_multiple" value="1"
                                       class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       {{ old('allows_multiple') ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 px-2 py-1 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800 font-medium">
                                    Allow multiple answers
                                </span>
                            </label>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                            Create at least 2 options and select the correct answer(s).
                        </p>
                        <div id="options-container" class="space-y-6">
                            {{-- Options injected by JS --}}
                        </div>
                        <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-options">
                            You must provide at least 2 valid options and select a correct answer.
                        </p>
                        <button type="button" id="btn-add-option"
                                class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition dark:border-indigo-900/50 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Option
                        </button>
                    </div>

                    {{-- True / False --}}
                    <div id="section-true-false" class="space-y-4 question-type-section hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">
                                Correct Answer <span class="text-red-500">*</span>
                            </h4>
                        </div>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="True"
                                       class="w-5 h-5 text-indigo-600 focus:ring-indigo-500"
                                       {{ old('correct_answer') === 'True' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white ml-2">True</span>
                            </label>
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="False"
                                       class="w-5 h-5 text-indigo-600 focus:ring-indigo-500"
                                       {{ old('correct_answer') === 'False' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white ml-2">False</span>
                            </label>
                        </div>
                        <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-tf">You must select True or False.</p>
                    </div>

                    {{-- Short / Text Answer --}}
                    <div id="section-short-answer" class="space-y-4 question-type-section hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">
                                Reference Answer <span class="text-red-500">*</span>
                            </h4>
                        </div>
                        <div>
                            <textarea id="sa_answer" name="sa_answer" class="w-full hidden">{{ old('correct_answer') }}</textarea>
                            <div id="editor-sa-answer" class="ck-editor-container"></div>
                            <p class="text-red-500 text-xs hidden mt-1 font-semibold" id="err-sa">Reference answer is required.</p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Text answer grading is typically manual. Provide a reference answer for graders.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Explanation ──────────────────────────────────────────────── --}}
                <div class="w-full relative z-0 border-t border-slate-200 dark:border-slate-800 pt-6 mt-4">
                    <label for="explanation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Answer Explanation <span class="text-xs font-normal text-slate-400">(optional)</span>
                    </label>
                    <textarea id="explanation" name="explanation" class="w-full hidden">{{ old('explanation') }}</textarea>
                    <div id="editor-explanation" class="ck-editor-container"></div>
                </div>

                {{-- ── SEO / Metadata ───────────────────────────────────────────── --}}
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 mt-4" x-data="{ open: false }">
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
                                       maxlength="255"
                                       class="panel-input w-full"
                                       placeholder="SEO page title (max 255 chars)">
                            </div>
                            <div>
                                <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug</label>
                                <input type="text" id="slug" name="slug"
                                       value="{{ old('slug') }}"
                                       maxlength="255"
                                       class="panel-input w-full"
                                       placeholder="e.g. what-is-2-plus-2">
                            </div>
                        </div>
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Description</label>
                            <textarea id="meta_description" name="meta_description"
                                      rows="2" maxlength="500"
                                      class="panel-input w-full"
                                      placeholder="Brief description for search engines (max 500 chars)">{{ old('meta_description') }}</textarea>
                        </div>
                        <div>
                            <label for="meta_keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Keywords</label>
                            <input type="text" id="meta_keywords" name="meta_keywords"
                                   value="{{ old('meta_keywords') }}"
                                   maxlength="500"
                                   class="panel-input w-full"
                                   placeholder="keyword1, keyword2, keyword3">
                        </div>
                        <div>
                            <label for="canonical_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Canonical URL</label>
                            <input type="url" id="canonical_url" name="canonical_url"
                                   value="{{ old('canonical_url') }}"
                                   class="panel-input w-full"
                                   placeholder="https://example.com/questions/...">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="og_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Title</label>
                                <input type="text" id="og_title" name="og_title"
                                       value="{{ old('og_title') }}"
                                       maxlength="255"
                                       class="panel-input w-full"
                                       placeholder="Open Graph title">
                            </div>
                            <div>
                                <label for="og_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Description</label>
                                <textarea id="og_description" name="og_description"
                                          rows="2" maxlength="500"
                                          class="panel-input w-full"
                                          placeholder="Open Graph description">{{ old('og_description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /px-4 py-5 --}}

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.questions.index') }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Save Question</button>
            </div>
        </form>
    </x-page-card>
</div>

{{-- Option Template --}}
<template id="option-template">
    <div class="option-item relative p-4 bg-slate-50 dark:bg-slate-900/30 border border-slate-200 dark:border-slate-700 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <label class="flex items-center justify-center p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer" title="Mark as correct">
                    <input type="radio" class="correct-answer-input w-5 h-5 text-emerald-500 border-slate-300 focus:ring-emerald-500 focus:ring-2 disabled:opacity-50">
                </label>
            </div>
            <div class="flex-grow min-w-0 relative z-0">
                <div class="option-editor-container min-h-[100px] border border-slate-300 dark:border-slate-600 rounded-lg overflow-hidden bg-white dark:bg-slate-950"></div>
            </div>
            <div class="flex-shrink-0">
                <button type="button" class="btn-remove-option p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition" title="Remove Option">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-create.css') }}">
    <style>
        .ts-control { border: 1px solid #e2e8f0; border-radius: .5rem; padding: .5rem .75rem; min-height: 2.5rem; background-color: #fff; }
        .dark .ts-control { border-color: #334155; background-color: #0f172a; color: #f8fafc; }
        .dark .ts-dropdown, .dark .ts-dropdown .option { background-color: #0f172a; color: #f8fafc; }
        .dark .ts-dropdown .option.active { background-color: #1e293b; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new TomSelect('#category_id', { create: false, placeholder: 'Search for a category…' });
        });
    </script>
    <script src="{{ asset('js/backend/question-create.js') }}"></script>
@endpush
