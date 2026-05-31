@extends('backend.layouts.app')

@section('title', 'Edit Question')
@section('page-title', 'Edit Question')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Questions', 'url' => route('admin.questions.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<div class="w-full">
    <x-page-card class="overflow-visible">
        <form action="{{ route('admin.questions.update', $question ?? 0) }}" method="POST"
              id="question-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="px-4 py-5 sm:px-6 border-b border-slate-200/80 dark:border-slate-800">
                <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white">Question Details</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Update the question content, options, answers, and metadata.
                </p>
            </div>

            <div class="px-4 py-5 sm:p-6 space-y-6">

                {{-- ── Top Row: Category, Type, Difficulty, Marks ─────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Category
                        </label>
                        <select id="category_id" name="category_id" class="panel-input mt-1 block w-full">
                            <option value="">Select Category…</option>
                            @foreach ($categories ?? [] as $cat)
                                <option value="{{ $cat->id }}"
                                    @selected(old('category_id', $question->category_id ?? null) == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Question Type <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type" class="panel-input mt-1 block w-full">
                            <option value="mcq"          @selected(old('type', $question->type ?? 'mcq') === 'mcq')>Multiple Choice</option>
                            <option value="true_false"   @selected(old('type', $question->type ?? '') === 'true_false')>True / False</option>
                            <option value="short_answer" @selected(old('type', $question->type ?? '') === 'short_answer')>Text Answer</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Difficulty <span class="text-red-500">*</span>
                        </label>
                        <select id="difficulty" name="difficulty" class="panel-input mt-1 block w-full">
                            <option value="easy"   @selected(old('difficulty', $question->difficulty ?? '') === 'easy')>Easy</option>
                            <option value="medium" @selected(old('difficulty', $question->difficulty ?? 'medium') === 'medium')>Medium</option>
                            <option value="hard"   @selected(old('difficulty', $question->difficulty ?? '') === 'hard')>Hard</option>
                        </select>
                        @error('difficulty')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="marks" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Marks <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="marks" name="marks"
                               value="{{ old('marks', $question->marks ?? 1) }}"
                               min="1" max="100" class="panel-input mt-1 block w-full">
                        @error('marks')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ── Status & Previously Asked ───────────────────────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:max-w-xs">
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="panel-input mt-1 block w-full">
                            <option value="active"    @selected(old('status', $question->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive"  @selected(old('status', $question->status ?? '') === 'inactive')>Inactive</option>
                            <option value="suspended" @selected(old('status', $question->status ?? '') === 'suspended')>Suspended</option>
                        </select>
                    </div>
                    <div>
                        <label for="previous_exam" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Previously Asked In
                        </label>
                        <input type="text" id="previous_exam" name="previous_exam"
                               value="{{ old('previous_exam', $question->previous_exam ?? '') }}"
                               class="panel-input mt-1 block w-full"
                               placeholder="e.g. UPSC Prelims 2023">
                    </div>
                </div>

                {{-- ── Question Body ────────────────────────────────────────────── --}}
                <div class="w-full relative z-0">
                    <label for="body" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Question Content <span class="text-red-500">*</span>
                    </label>
                    <textarea id="body" name="body" class="w-full hidden">{{ old('body', $question->body ?? '') }}</textarea>
                    <div id="editor-body" class="ck-editor-container"></div>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── Dynamic Answer Sections ──────────────────────────────────── --}}
                <div id="dynamic-sections" class="w-full min-h-[300px]">

                    {{-- MCQ --}}
                    <div id="section-mcq" class="space-y-4 question-type-section">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">Options</h4>
                            <label class="flex items-center cursor-pointer gap-2">
                                <input type="checkbox" id="allows_multiple" name="allows_multiple" value="1"
                                       class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       {{ old('allows_multiple', $question->allows_multiple ?? false) ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700 dark:text-slate-300">Allow multiple answers</span>
                            </label>
                        </div>
                        <div id="options-container" class="space-y-6">
                            {{-- Options injected by JS --}}
                        </div>
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
                        <h4 class="text-base font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-2">
                            Correct Answer
                        </h4>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="True"
                                       class="text-indigo-600 focus:ring-indigo-500"
                                       {{ old('correct_answer', $question->correct_answer ?? '') === 'True' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white">True</span>
                            </label>
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="False"
                                       class="text-indigo-600 focus:ring-indigo-500"
                                       {{ old('correct_answer', $question->correct_answer ?? '') === 'False' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white">False</span>
                            </label>
                        </div>
                    </div>

                    {{-- Short Answer --}}
                    <div id="section-short-answer" class="space-y-4 question-type-section hidden">
                        <h4 class="text-base font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-2">
                            Reference Answer
                        </h4>
                        <div>
                            <textarea name="sa_answer" class="panel-input w-full h-24"
                                      placeholder="Enter reference correct answer or keywords…">{{ old('correct_answer', $question->correct_answer ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Short answer grading is typically manual. Provide a reference answer here.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Explanation ──────────────────────────────────────────────── --}}
                <div class="w-full relative z-0 border-t border-slate-200 dark:border-slate-800 pt-6">
                    <label for="explanation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Answer Explanation <span class="text-xs font-normal text-slate-400">(optional)</span>
                    </label>
                    <textarea id="explanation" name="explanation" class="w-full hidden">{{ old('explanation', $question->explanation ?? '') }}</textarea>
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
                                       value="{{ old('meta_title', $question->meta_title ?? '') }}"
                                       maxlength="255" class="panel-input w-full" placeholder="SEO page title">
                            </div>
                            <div>
                                <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug</label>
                                <input type="text" id="slug" name="slug"
                                       value="{{ old('slug', $question->slug ?? '') }}"
                                       maxlength="255" class="panel-input w-full" placeholder="e.g. what-is-2-plus-2">
                            </div>
                        </div>
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="2" maxlength="500"
                                      class="panel-input w-full"
                                      placeholder="Brief description for search engines">{{ old('meta_description', $question->meta_description ?? '') }}</textarea>
                        </div>
                        <div>
                            <label for="meta_keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta Keywords</label>
                            <input type="text" id="meta_keywords" name="meta_keywords"
                                   value="{{ old('meta_keywords', $question->meta_keywords ?? '') }}"
                                   maxlength="500" class="panel-input w-full" placeholder="keyword1, keyword2">
                        </div>
                        <div>
                            <label for="canonical_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Canonical URL</label>
                            <input type="url" id="canonical_url" name="canonical_url"
                                   value="{{ old('canonical_url', $question->canonical_url ?? '') }}"
                                   class="panel-input w-full" placeholder="https://example.com/questions/...">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="og_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Title</label>
                                <input type="text" id="og_title" name="og_title"
                                       value="{{ old('og_title', $question->og_title ?? '') }}"
                                       maxlength="255" class="panel-input w-full" placeholder="Open Graph title">
                            </div>
                            <div>
                                <label for="og_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">OG Description</label>
                                <textarea id="og_description" name="og_description" rows="2" maxlength="500"
                                          class="panel-input w-full"
                                          placeholder="Open Graph description">{{ old('og_description', $question->og_description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /px-4 py-5 --}}

            <div class="px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.questions.index') }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Update Question</button>
            </div>
        </form>
    </x-page-card>
</div>

{{-- Option Template --}}
<template id="option-template">
    <div class="option-item relative p-4 bg-slate-50 dark:bg-slate-900/30 border border-slate-200 dark:border-slate-700 rounded-xl">
        <div class="absolute top-4 right-4 z-10 flex gap-2">
            <button type="button" class="btn-remove-option p-1 text-slate-400 hover:text-red-500 transition" title="Remove Option">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex items-start gap-4">
            <div class="pt-2 flex-shrink-0">
                <label class="flex items-center justify-center p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer" title="Mark as correct">
                    <input type="radio" class="correct-answer-input w-5 h-5 text-emerald-500 border-slate-300 focus:ring-emerald-500 focus:ring-2 disabled:opacity-50">
                </label>
            </div>
            <div class="flex-grow min-w-0 relative z-0">
                <div class="option-editor-container min-h-[100px] border border-slate-300 dark:border-slate-600 rounded-lg overflow-hidden bg-white dark:bg-slate-950"></div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new TomSelect('#category_id', { create: false, placeholder: 'Search for a category…' });
        });
    </script>
    <script src="{{ asset('js/backend/question-create.js') }}"></script>
@endpush
