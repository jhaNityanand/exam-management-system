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
<div class="w-full">
    <x-page-card class="overflow-visible">
        <form action="{{ route('admin.questions.store') }}" method="POST" id="question-form" enctype="multipart/form-data">
            @csrf
            
            <div class="px-4 py-5 sm:px-6 border-b border-slate-200/80 dark:border-slate-800">
                <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white">Question Details</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add a new question to the repository with options, answers, and explanations.</p>
            </div>
            
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <!-- Top Row: Category, Type, Difficulty, Marks -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                        <select id="category_id" name="category_id" class="panel-input mt-1 block w-full">
                            <option value="">Select Category...</option>
                            <option value="1" class="font-semibold text-slate-900">Science</option>
                            <option value="2">&nbsp;&nbsp;&nbsp;&nbsp;Physics</option>
                            <option value="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Classical Mechanics</option>
                            <option value="4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Quantum Physics</option>
                            <option value="6">&nbsp;&nbsp;&nbsp;&nbsp;Biology</option>
                            <option value="8">&nbsp;&nbsp;&nbsp;&nbsp;Chemistry</option>
                            <option value="9" class="font-semibold text-slate-900">Mathematics</option>
                            <option value="10">&nbsp;&nbsp;&nbsp;&nbsp;Algebra</option>
                            <option value="11">&nbsp;&nbsp;&nbsp;&nbsp;Geometry</option>
                            <option value="12">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Trigonometry</option>
                            <option value="14" class="font-semibold text-slate-900">Computer Science</option>
                            <option value="15">&nbsp;&nbsp;&nbsp;&nbsp;Programming</option>
                            <option value="16">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Web Development</option>
                            <option value="17">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data Structures</option>
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Question Type</label>
                        <select id="type" name="type" class="panel-input mt-1 block w-full">
                            <option value="mcq" {{ old('type') == 'mcq' ? 'selected' : '' }}>Multiple Choice</option>
                            <option value="true_false" {{ old('type') == 'true_false' ? 'selected' : '' }}>True/False</option>
                            <option value="short_answer" {{ old('type') == 'short_answer' ? 'selected' : '' }}>Short Answer</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Difficulty</label>
                        <select id="difficulty" name="difficulty" class="panel-input mt-1 block w-full">
                            <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="medium" {{ old('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
                        </select>
                        @error('difficulty')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="marks" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Marks</label>
                        <input type="number" id="marks" name="marks" value="{{ old('marks', 1) }}" min="1" max="100" class="panel-input mt-1 block w-full">
                        @error('marks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status Row -->
                <div class="sm:max-w-xs shrink-0">
                    <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                    <select id="status" name="status" class="panel-input mt-1 block w-full">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Question Body -->
                <div class="w-full relative z-0">
                    <label for="body" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Question Content <span class="text-red-500">*</span></label>
                    <textarea id="body" name="body" class="w-full hidden">{{ old('body') }}</textarea>
                    <!-- CKEditor container -->
                    <div id="editor-body" class="ck-editor-container"></div>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Dynamic Sections based on Type -->
                <div id="dynamic-sections" class="w-full min-h-[300px]">
                    <!-- MCQ Section -->
                    <div id="section-mcq" class="space-y-4 question-type-section">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white">Options</h4>
                            
                            <label class="flex items-center cursor-pointer gap-2">
                                <input type="checkbox" id="allows_multiple" name="allows_multiple" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ old('allows_multiple') ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700 dark:text-slate-300">Allow multiple answers</span>
                            </label>
                        </div>
                        
                        <div id="options-container" class="space-y-6">
                            <!-- Options injected by JS -->
                        </div>
                        
                        <button type="button" id="btn-add-option" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition dark:border-indigo-900/50 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Option
                        </button>
                    </div>

                    <!-- True/False Section -->
                    <div id="section-true-false" class="space-y-4 question-type-section hidden">
                        <h4 class="text-base font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-2">Correct Answer</h4>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="True" class="text-indigo-600 focus:ring-indigo-500" {{ old('correct_answer') == 'True' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white">True</span>
                            </label>
                            <label class="flex items-center gap-2 p-4 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex-1">
                                <input type="radio" name="tf_answer" value="False" class="text-indigo-600 focus:ring-indigo-500" {{ old('correct_answer') == 'False' ? 'checked' : '' }}>
                                <span class="font-medium text-slate-900 dark:text-white">False</span>
                            </label>
                        </div>
                    </div>

                    <!-- Short Answer Section -->
                    <div id="section-short-answer" class="space-y-4 question-type-section hidden">
                        <h4 class="text-base font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-2">Reference Answer</h4>
                        <div>
                            <textarea name="sa_answer" class="panel-input w-full h-24" placeholder="Enter reference correct answer or keywords...">{{ old('correct_answer') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">Short answer grading is typically manual, but you can provide a reference answer here.</p>
                        </div>
                    </div>
                </div>

                <!-- Explanation Section -->
                <div class="w-full relative z-0 border-t border-slate-200 dark:border-slate-800 pt-6">
                    <label for="explanation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Answer Explanation (Optional)</label>
                    <textarea id="explanation" name="explanation" class="w-full hidden">{{ old('explanation') }}</textarea>
                    <div id="editor-explanation" class="ck-editor-container"></div>
                </div>
            </div>
            
            <div class="px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 rounded-b-2xl">
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
        <div class="absolute top-4 right-4 z-10 flex gap-2">
            <button type="button" class="btn-remove-option p-1 text-slate-400 hover:text-red-500 transition" title="Remove Option">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
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
        .ts-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            min-height: 2.5rem;
            background-color: #fff;
        }
        .dark .ts-control {
            border-color: #334155;
            background-color: #0f172a;
            color: #f8fafc;
        }
        .dark .ts-dropdown, .dark .ts-dropdown .option {
            background-color: #0f172a;
            color: #f8fafc;
        }
        .dark .ts-dropdown .option.active {
            background-color: #1e293b;
        }
    </style>
@endpush

@push('scripts')
    {{-- Using ClassicEditor build from CDN --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#category_id',{
                create: false,
                placeholder: "Search for a category..."
            });
        });
    </script>
    <script src="{{ asset('js/backend/question-create.js') }}"></script>
@endpush
