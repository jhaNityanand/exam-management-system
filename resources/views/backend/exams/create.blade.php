@extends('backend.layouts.app')

@section('title', 'Create Exam')
@section('page-title', 'Create Exam')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin',  'url' => route('admin.dashboard')],
        ['label' => 'Exams',  'url' => route('admin.exams.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<div id="exam-create-page" class="exam-create-page" data-page-ready="false">
    <div id="exam-page-loader" class="exam-page-loader" aria-live="polite" aria-busy="true">
        <div class="exam-page-loader__inner">
            <span class="exam-page-loader__spinner" aria-hidden="true"></span>
            <p>Preparing exam creation workspace...</p>
        </div>
    </div>

    <x-page-card class="exam-shell-card overflow-hidden">
        @include('backend.exams._form', [
            'formAction' => route('admin.exams.store'),
            'httpMethod' => 'POST',
            'exam' => null,
            'categories' => $categories,
            'formOptions' => $formOptions,
            'pageHeading' => 'Create Exam',
            'pageSubheading' => 'Build a structured exam flow with clear rules, candidate access control, and question availability checks.',
            'headerBadge' => 'Draft Workspace',
            'submitLabel' => 'Save Exam',
            'cancelUrl' => route('admin.exams.index'),
        ])
    </x-page-card>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}?v={{ filemtime(public_path('css/backend/tom-select-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modules/form-utils.css') }}?v={{ filemtime(public_path('css/modules/form-utils.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-create.css') }}?v={{ filemtime(public_path('css/backend/exam-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/question-bank-accordion.css') }}?v={{ filemtime(public_path('css/question-bank-accordion.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}?v={{ filemtime(public_path('css/backend/question-category-form.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ filemtime(public_path('js/components/editor.js')) }}"></script>
    <script src="{{ asset('js/components/select.js') }}?v={{ filemtime(public_path('js/components/select.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}?v={{ filemtime(public_path('js/components/tom-select-blur.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ filemtime(public_path('js/components/tom-select-hierarchy.js')) }}"></script>
    <script src="{{ asset('js/components/question-bank-accordion.js') }}?v={{ filemtime(public_path('js/components/question-bank-accordion.js')) }}"></script>
    <script src="{{ asset('js/backend/question-bank-init.js') }}?v={{ filemtime(public_path('js/backend/question-bank-init.js')) }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}?v={{ filemtime(public_path('js/backend/seo-manager.js')) }}"></script>
    <script src="{{ asset('js/core/form-utils.js') }}?v={{ filemtime(public_path('js/core/form-utils.js')) }}"></script>
    <script>
        window.examCreateConfig = {
            options: @json($formOptions),
            endpoints: {
                // Categories only on first load. Question bank loads on demand (filters) to avoid timeouts.
                categories: @json(route('admin.api.question-bank.categories')),
                questionBank: @json(route('admin.api.question-bank.questions')),
                questionBankRandom: @json(route('admin.api.question-bank.random')),
                questionCreate: @json(route('admin.questions.create')),
                examCreate: @json(route('admin.exams.create')),
            },
            bootstrapEndpoints: {
                categories: @json(route('admin.api.question-bank.categories')),
            },
        };
        // No exam to hydrate on create — the form starts from wizard defaults.
        window.examFormConfig = null;
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = window.EmsTomSelectHierarchy?.create('#exam_category_id', {
                placeholder: "Search for a category...",
            }) || new TomSelect('#exam_category_id', {
                create: false,
                placeholder: "Search for a category...",
                closeAfterSelect: true,
            });
            if (!window.EmsTomSelectHierarchy) {
                window.EmsTomSelectBlur?.attach(categorySelect);
            }
            window.EmsTomSelectBlur?.blurNativeSelects(document.querySelector('form') || document);
        });
    </script>
    <script src="{{ asset('js/backend/exam-create.js') }}?v={{ filemtime(public_path('js/backend/exam-create.js')) }}"></script>
@endpush
