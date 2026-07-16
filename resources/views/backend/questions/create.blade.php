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
                    <h1 class="category-builder__title tracking-tight text-slate-900">Create Question</h1>
                    <p class="category-builder__subtitle text-slate-500">
                        Add a new question to the repository with options, answers, and explanations.
                    </p>
                </div>
            </div>

            <div class="px-4 py-5 sm:p-6 space-y-8">
                <!-- Row 1: Category, Type, Difficulty, Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="mt-1 block w-full">
                            <option value="">Search or select...</option>
                            @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                data-level="{{ $cat->depth }}"
                                data-category-name="{{ $cat->name }}"
                                class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}"
                                {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="qcat-field-error" id="err-category_id"></p>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Question Type <span class="text-red-500">*</span></label>
                        <select id="type" name="type" class="panel-input mt-1 block w-full">
                            @foreach(($questionTypes ?? \App\Support\ExamFormats::questionTypes()) as $type)
                                <option value="{{ $type['id'] }}" {{ old('type') == $type['id'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="qcat-field-error" id="err-type"></p>
                    </div>

                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty <span class="text-red-500">*</span></label>
                        <select id="difficulty" name="difficulty" class="panel-input mt-1 block w-full">
                            <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="medium" {{ old('difficulty', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
                            <option value="very_hard" {{ old('difficulty') == 'very_hard' ? 'selected' : '' }}>Very Hard</option>
                        </select>
                        <p class="qcat-field-error" id="err-difficulty"></p>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="panel-input mt-1 block w-full">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <p class="qcat-field-error" id="err-status"></p>
                    </div>
                </div>

                <!-- Row 2: Reference, Marks Type, Marks -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 shrink-0 pt-2">
                    {{-- Reference --}}
                    <div class="lg:col-span-3">
                        <label for="reference" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Reference
                        </label>
                        <input type="text" id="reference" name="reference" 
                               value="{{ old('reference') }}" 
                               placeholder="e.g. UPSC Prelims 2023" 
                               class="panel-input mt-1 block w-full text-sm">
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Short note referencing the source exam/book.</p>
                        <p class="qcat-field-error" id="err-reference"></p>
                    </div>

                    {{-- Marks Type --}}
                    <div class="lg:col-span-3">
                        <label for="marks_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Marks Type <span class="text-red-500">*</span></label>
                        <select id="marks_type" name="marks_type" class="panel-input mt-1 block w-full">
                            <option value="single" {{ old('marks_type', 'single') == 'single' ? 'selected' : '' }}>Single Marks</option>
                            <option value="multiple" {{ old('marks_type') == 'multiple' ? 'selected' : '' }}>Multiple Marks</option>
                        </select>
                        <p class="qcat-field-error" id="err-marks_type"></p>
                    </div>

                    {{-- Marks --}}
                    <div class="lg:col-span-6">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Marks <span class="text-red-500">*</span></label>
                        <!-- Modern Interactive Marks Pill Container -->
                        <div id="marks-pills-container" class="flex flex-wrap gap-2 mt-1 p-2 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl">
                            @for ($i = 1; $i <= 10; $i++)
                                <button type="button" data-value="{{ $i }}" class="marks-pill-btn px-4 py-2 text-sm font-semibold border rounded-lg transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>
                        <p class="qcat-field-error" id="err-marks"></p>

                        <!-- Hidden fields for value binding -->
                        <input type="hidden" name="marks" id="marks" value="{{ old('marks', 1) }}">
                        <select name="marks_list[]" id="marks_list" class="hidden" multiple>
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ in_array($i, old('marks_list', [])) ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Question Body -->
                <div class="w-full relative z-0 pt-4">
                    <x-rich-text-editor
                        label="Question Content"
                        input-id="body"
                        name="body"
                        :value="old('body')"
                        placeholder="Type your question content here…"
                        :height="260"
                        preset="full"
                                    module="question"
                        :required="true"
                        wrapper-class="ems-rich-editor--question"
                    />
                    <p class="qcat-field-error" id="err-body"></p>
                </div>

                <!-- Dynamic Sections based on Type -->
                <div id="dynamic-sections" class="w-full min-h-[300px] mt-4">
                    <!-- MCQ Section -->
                    <div id="section-mcq" class="space-y-4 question-type-section">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">Options <span class="text-red-500">*</span></h4>

                            <label class="flex items-center cursor-pointer gap-2">
                                <input type="checkbox" id="allows_multiple" name="allows_multiple" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ old('allows_multiple') ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 px-2 py-1 rounded-md hover:bg-slate-50 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100 font-medium">Allow multiple answers</span>
                            </label>
                        </div>
                        <p class="text-sm text-slate-500 mb-2">Create at least 2 options and select the correct answer(s).</p>

                        <div id="options-container" class="space-y-6">
                            <!-- Options injected by JS -->
                        </div>
                        <p class="qcat-field-error" id="err-options"></p>

                        <button type="button" id="btn-add-option" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition dark:border-indigo-900/50 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Option
                        </button>
                    </div>

                    <!-- True/False Section -->
                    <div id="section-true-false" class="space-y-4 question-type-section hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">Correct Answer <span class="text-red-500">*</span></h4>
                        </div>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="True" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" {{ old('correct_answer') == 'True' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white ml-2">True</span>
                            </label>
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="False" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" {{ old('correct_answer') == 'False' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white ml-2">False</span>
                            </label>
                        </div>
                        <p class="qcat-field-error" id="err-tf"></p>
                    </div>

                    <!-- Text Answer Section -->
                    <div id="section-short-answer" class="space-y-4 question-type-section hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">Reference Answer <span class="text-red-500">*</span></h4>
                        </div>
                        <div>
                            <x-rich-text-editor
                                label="Reference Answer"
                                input-id="sa_answer"
                                name="sa_answer"
                                :value="old('correct_answer')"
                                placeholder="Provide a reference answer for graders…"
                                :height="200"
                                preset="standard"
                                module="question"
                                help="Text answer grading is typically manual, but you can provide a reference answer here for graders."
                            />
                            <p class="qcat-field-error" id="err-sa"></p>
                        </div>
                    </div>
                </div>

                <!-- Explanation Section -->
                <div class="w-full relative z-0 border-t border-slate-200 dark:border-slate-800 pt-6 mt-4">
                    <x-rich-text-editor
                        label="Answer Explanation"
                        input-id="explanation"
                        name="explanation"
                        :value="old('explanation')"
                        placeholder="Optional explanation shown after grading…"
                        :height="220"
                        preset="full"
                                module="question"
                    />
                </div>

            </div>

            {{-- ═══════════════════════════════════════════════════════════════
            SEO & METADATA SECTION (Identical across all Create & Edit views)
            ════════════════════════════════════════════════════════════════ --}}
                @php
                    $seoItem = $category ?? $question ?? null;
                @endphp
                <div id="metadata-section" class="category-builder__metadata">
                    <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false" tabindex="0">
                        <div class="qcat-meta-header-left">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="qcat-meta-title">SEO &amp; Metadata</span>
                            <span class="qcat-meta-badge">Optional</span>
                        </div>
                        <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div id="meta-accordion-body" class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <p class="qcat-meta-hint mb-4">Add SEO keywords, meta details, and titles to index this content properly.</p>

                        <!-- Row 1: AI Toggles -->
                        <div class="qcat-seo-row qcat-seo-row--toggles">
                            {{-- Create with AI --}}
                            <div class="qcat-seo-col col-lg-4">
                                <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                                    <input type="hidden" name="ai_generated" value="0">
                                    <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                                        class="qcat-ai-checkbox" @checked(old('ai_generated', $seoItem?->ai_generated ?? false))>
                                    <span class="qcat-ai-toggle-wrap">
                                        <span class="qcat-ai-thumb"></span>
                                    </span>
                                    <span class="qcat-ai-text">
                                        <span class="qcat-ai-title">Create with AI</span>
                                        <span class="qcat-ai-hint">Let AI generate details automatically</span>
                                    </span>
                                </label>
                            </div>

                            {{-- Improve with AI --}}
                            <div class="qcat-seo-col col-lg-4" id="improve-with-ai-wrapper">
                                <label class="qcat-ai-toggle-label" for="toggle-ai-improve">
                                    <input type="hidden" name="ai_improve" value="0">
                                    <input type="checkbox" name="ai_improve" id="toggle-ai-improve" value="1"
                                        class="qcat-ai-checkbox" @checked(old('ai_improve', $seoItem?->ai_improve ?? false))>
                                    <span class="qcat-ai-toggle-wrap">
                                        <span class="qcat-ai-thumb"></span>
                                    </span>
                                    <span class="qcat-ai-text">
                                        <span class="qcat-ai-title">Improve with AI</span>
                                        <span class="qcat-ai-hint">Queue for AI improvement</span>
                                    </span>
                                </label>
                            </div>

                            {{-- Empty column for layout balance --}}
                            <div class="qcat-seo-col col-lg-4"></div>
                        </div>

                        <!-- Manual SEO Fields Wrapper -->
                        <div id="manual-seo-fields-wrapper" class="space-y-4">
                            <!-- Row 2: Meta Title, Slug, OG Title (col-lg-4 each) -->
                            <div class="qcat-seo-row qcat-seo-row--three-cols">
                                {{-- Meta Title --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                                    <input type="text" id="meta-title" name="meta_title" value="{{ old('meta_title', $seoItem?->meta_title ?? '') }}" placeholder="e.g. Meta Title" class="panel-input qcat-meta-input">
                                    <span class="qcat-meta-count" data-max="255">0 / 255</span>
                                    @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- Slug --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-slug">Slug</label>
                                    <input type="text" id="meta-slug" name="slug" value="{{ old('slug', $seoItem?->slug ?? '') }}" placeholder="e.g. slug-value" class="panel-input qcat-meta-input">
                                    @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- OG Title --}}
                                <div class="qcat-meta-field col-lg-4">
                                    <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                                    <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" placeholder="e.g. Open Graph Title" class="panel-input qcat-meta-input">
                                    @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Row 3: Meta Description, OG Description (col-lg-6 each) -->
                            <div class="qcat-seo-row qcat-seo-row--two-cols">
                                {{-- Meta Description --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                                    <textarea id="meta-desc" name="meta_description" rows="2" placeholder="Brief description for search engines (up to 500 characters)" class="panel-input qcat-meta-textarea">{{ old('meta_description', $seoItem?->meta_description ?? '') }}</textarea>
                                    <span class="qcat-meta-count" data-max="500">0 / 500</span>
                                    @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- OG Description --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                                    <textarea id="meta-og-desc" name="og_description" rows="2" placeholder="Open Graph Description" class="panel-input qcat-meta-textarea">{{ old('og_description', $seoItem?->og_description ?? '') }}</textarea>
                                    @error('og_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Row 4: Meta Keywords, Canonical URL (col-lg-6 each) -->
                            <div class="qcat-seo-row qcat-seo-row--two-cols">
                                {{-- Meta Keywords --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-keywords">Meta Keywords</label>
                                    <input type="text" id="meta-keywords" name="meta_keywords" value="{{ old('meta_keywords', $seoItem?->meta_keywords ?? '') }}" placeholder="keywords, comma, separated" class="panel-input qcat-meta-input">
                                    @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>

                                {{-- Canonical URL --}}
                                <div class="qcat-meta-field col-lg-6">
                                    <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                                    <input type="url" id="meta-canonical" name="canonical_url" value="{{ old('canonical_url', $seoItem?->canonical_url ?? '') }}" placeholder="https://example.com/canonical" class="panel-input qcat-meta-input">
                                    @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.questions.index') }}" class="panel-button-secondary">
                    Cancel
                </a>
                <button type="submit" class="panel-button-primary" id="btn-submit">
                    Save Question
                </button>
            </div>
        </form>
    </x-page-card>
</div>

<!-- Option Template -->
<template id="option-template">
    <div class="option-item relative p-4 bg-slate-50 dark:bg-slate-900/30 border border-slate-200 dark:border-slate-700 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <label class="flex items-center justify-center p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer" title="Mark as correct">
                    <input type="radio" class="correct-answer-input w-5 h-5 text-emerald-500 border-slate-300 focus:ring-emerald-500 focus:ring-2 disabled:opacity-50">
                </label>
            </div>

            <div class="flex-grow min-w-0 relative z-0">
                <textarea class="option-editor-textarea panel-input min-h-[100px]" rows="3" data-option-editor placeholder="Enter option text…"></textarea>
            </div>

            <div class="flex-shrink-0">
                <button type="button" class="btn-remove-option p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-full transition" title="Remove Option">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    </div>
</template>

@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-create.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ time() }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ time() }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = window.EmsTomSelectHierarchy?.create('#category_id', {
                placeholder: 'Search or select a category…',
            }) || new TomSelect('#category_id', {
                create: false,
                placeholder: 'Search or select a category…',
                closeAfterSelect: true,
            });
            window.EmsTomSelectBlur?.attach(categorySelect);
            window.EmsTomSelectBlur?.blurNativeSelects(document.getElementById('question-form') || document);
        });
    </script>
    <script src="{{ asset('js/backend/question-create.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
@endpush
