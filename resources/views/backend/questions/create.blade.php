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
@php
    $defaults = $defaults ?? [
        'source' => null,
        'category_id' => null,
        'type' => null,
        'allows_multiple' => null,
        'difficulty' => null,
        'marks_type' => 'single',
        'marks' => null,
        'marks_list' => [],
        'formats' => [],
    ];
@endphp
<div class="w-full relative">
    <x-page-card class="category-builder-card overflow-visible relative z-10 w-full">
        <form action="{{ route('admin.questions.store') }}" method="POST" id="question-form" enctype="multipart/form-data" class="category-builder">
            @csrf

            <div class="category-builder__header px-4 py-6 sm:px-6">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900">
                        {{ ($defaults['source'] ?? null) === 'exam-create' ? 'Create Question for Exam' : 'Create Question' }}
                    </h1>
                    <p class="category-builder__subtitle text-slate-500">
                        @if(($defaults['source'] ?? null) === 'exam-create')
                            Prefill values from Exam Create. After saving, this tab will close and refresh the exam question bank.
                        @else
                            Add a new question to the repository with options, answers, and explanations.
                        @endif
                    </p>
                </div>
            </div>

            @if(($defaults['source'] ?? null) === 'exam-create')
                <input type="hidden" name="source" value="exam-create">
            @endif

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
                                {{ (string) old('category_id', $defaults['category_id'] ?? '') === (string) $cat->id ? 'selected' : '' }}>
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
                                <option value="{{ $type['id'] }}" {{ old('type', $defaults['type'] ?? '') == $type['id'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="qcat-field-error" id="err-type"></p>
                    </div>

                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty <span class="text-red-500">*</span></label>
                        <select id="difficulty" name="difficulty" class="panel-input mt-1 block w-full">
                            <option value="easy" {{ old('difficulty', $defaults['difficulty'] ?? '') == 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="medium" {{ old('difficulty', $defaults['difficulty'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="hard" {{ old('difficulty', $defaults['difficulty'] ?? '') == 'hard' ? 'selected' : '' }}>Hard</option>
                            <option value="very_hard" {{ old('difficulty', $defaults['difficulty'] ?? '') == 'very_hard' ? 'selected' : '' }}>Very Hard</option>
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
                            <option value="single" {{ old('marks_type', $defaults['marks_type'] ?? 'single') == 'single' ? 'selected' : '' }}>Single Marks</option>
                            <option value="multiple" {{ old('marks_type', $defaults['marks_type'] ?? 'single') == 'multiple' ? 'selected' : '' }}>Multiple Marks</option>
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
                        <input type="hidden" name="marks" id="marks" value="{{ old('marks', $defaults['marks'] ?? 1) }}">
                        <select name="marks_list[]" id="marks_list" class="hidden" multiple>
                            @php
                                $selectedMarksList = old('marks_list', $defaults['marks_list'] ?? []);
                            @endphp
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ in_array($i, $selectedMarksList, false) ? 'selected' : '' }}>{{ $i }}</option>
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
                                <input type="checkbox" id="allows_multiple" name="allows_multiple" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ old('allows_multiple', ($defaults['allows_multiple'] ?? false) ? '1' : null) ? 'checked' : '' }}>
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

            {{-- SEO & METADATA (shared partial — keep constant across modules) --}}
            @include('backend.partials.seo-metadata-section', [
                'seoItem' => $category ?? $question ?? null,
                'showSlug' => true,
                'slugPlaceholder' => 'auto-generated-from-question',
                'showPublishingExtras' => true,
                'previewBaseUrl' => url('/questions'),
                'previewClassPrefix' => 'question',
            ])

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

            @if(!empty($createdFromExam))
                (function () {
                    const payload = {
                        type: 'exam-create-question-created',
                        question: @json($createdFromExam),
                    };
                    const examCreateUrl = @json($examCreateReturn ?: route('admin.exams.create'));

                    try {
                        if (window.opener && !window.opener.closed) {
                            window.opener.postMessage(payload, window.location.origin);
                            window.close();
                            return;
                        }
                    } catch (error) {
                        console.warn('Unable to notify Exam Create opener.', error);
                    }

                    const notice = document.createElement('div');
                    notice.className = 'mx-4 mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200';
                    notice.innerHTML = 'Question created. <a class="font-semibold underline" href="' + examCreateUrl + '">Return to Exam Create</a> and refresh the question bank.';
                    const form = document.getElementById('question-form');
                    form?.parentElement?.insertBefore(notice, form);
                })();
            @endif
        });
    </script>
    <script src="{{ asset('js/backend/question-create.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
    <script src="{{ asset('js/backend/slug-field.js') }}?v={{ filemtime(public_path('js/backend/slug-field.js')) }}"></script>
    <script>
        window.slugResolveUrl = @json(route('admin.slug.resolve'));
        document.addEventListener('DOMContentLoaded', function () {
            window.EmsSlugField?.bind({
                module: 'question',
                sourceSelector: '#body',
                slugSelector: '#meta-slug',
                resolveUrl: window.slugResolveUrl,
                sourceIsHtml: true,
            });
        });
    </script>
@endpush
