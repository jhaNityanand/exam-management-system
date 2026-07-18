@extends('backend.layouts.app')

@section('title', 'Edit Exam — ' . $exam->title)
@section('page-title', 'Edit Exam')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exams', 'url' => route('admin.exams.index')],
        ['label' => $exam->title],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<div id="exam-create-page" class="exam-create-page" data-page-ready="false">
    <div id="exam-page-loader" class="exam-page-loader" aria-live="polite" aria-busy="true">
        <div class="exam-page-loader__inner">
            <span class="exam-page-loader__spinner" aria-hidden="true"></span>
            <p>Preparing exam edit workspace...</p>
        </div>
    </div>

    <x-page-card class="exam-shell-card overflow-hidden">
        @include('backend.exams._form', [
            'formAction' => route('admin.exams.update', $exam),
            'httpMethod' => 'PUT',
            'exam' => $exam,
            'categories' => $categories,
            'formOptions' => $formOptions,
            'pageHeading' => 'Edit Exam',
            'pageSubheading' => 'Update the exam identity, rules, candidate access, and question availability.',
            'headerBadge' => 'Exam #' . $exam->id,
            'submitLabel' => 'Update Exam',
            'cancelUrl' => route('admin.exams.show', $exam),
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
    @php
        $examFormatValue = is_array($exam->exam_format)
            ? $exam->exam_format
            : (json_decode($exam->exam_format ?? '[]', true) ?: []);

        $examConfig = [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'instructions' => $exam->instructions,
            'status' => $exam->status,
            'exam_mode' => $exam->exam_mode,
            'exam_format' => $examFormatValue ?: ['mcq'],
            'visibility' => $exam->visibility,
            'difficulty_level' => $exam->difficulty_level,
            'exam_category_id' => $exam->category_id,
            'tags' => $exam->tags ?? [],

            // Timer & duration
            'enable_exam_timer' => (bool) $exam->enable_exam_timer,
            'exam_duration_minutes' => $exam->duration,
            'auto_submit_on_timer_end' => (bool) $exam->auto_submit_on_timer_end,

            // Schedule & attempts
            'schedule_type' => $exam->schedule_type,
            'schedule_start_at' => optional($exam->scheduled_start)->format('Y-m-d H:i'),
            'schedule_end_at' => optional($exam->scheduled_end)->format('Y-m-d H:i'),
            'attempt_limit_type' => $exam->attempt_limit_type,
            'attempt_limit_count' => $exam->max_attempts,

            // Exam configuration
            'total_questions' => $exam->total_questions,
            'total_marks' => $exam->total_marks,
            'passing_marks' => $exam->passing_marks,
            'use_question_pool' => (bool) $exam->use_question_pool,
            'maximum_questions' => $exam->maximum_questions,
            'fixed_questions' => (bool) $exam->fixed_questions,
            'fixed_paper_set' => (bool) $exam->fixed_paper_set,
            'paper_sets' => $exam->paper_sets,
            'fix_category_questions' => (bool) $exam->fix_category_questions,
            'fix_category_marks' => (bool) $exam->fix_category_marks,
            'distribution_type' => $exam->distribution_type,
            'selected_categories' => $exam->selected_categories ?? [],
            'extra_questions_categories' => $exam->extra_questions_categories ?? [],
            'extra_questions_allocations' => $exam->extra_questions_allocations ?? [],
            'extra_marks_allocations' => $exam->extra_marks_allocations ?? [],
            'question_ids' => $exam->questions->pluck('id')->values()->all(),

            // Question rules & filters
            'fix_marks_each_question' => (bool) $exam->fix_marks_each_question,
            'question_marks_filter' => $exam->question_marks_filter ?? [],
            'enable_negative_marking' => (bool) $exam->enable_negative_marking,
            'negative_marking_type' => $exam->negative_marking_type,
            'negative_mark_per_question' => $exam->negative_mark_per_question,
            'shuffle_questions' => (bool) $exam->shuffle_questions,
            'shuffle_categories' => (bool) $exam->shuffle_categories,
            'shuffle_options' => (bool) $exam->shuffle_options,

            // Pricing & discounts
            'pricing_option' => $exam->pricing_option,
            'exam_currency' => $exam->exam_currency,
            'exam_amount' => $exam->exam_amount,
            'selected_discounts' => $exam->selected_discounts ?? [],
            'custom_discounts' => $exam->custom_discounts ?? [],

            // Candidate access
            'imported_candidates' => $exam->imported_candidates ?? [],
            'manual_candidate_emails' => $exam->manual_candidate_emails ?? [],
            'free_imported_candidates' => $exam->free_imported_candidates ?? [],
            'free_manual_candidate_emails' => $exam->free_manual_candidate_emails ?? [],

            // Instructions & rules
            'predefined_instruction_rules' => $exam->predefined_instruction_rules ?? [],

            // SEO / metadata
            'meta_title' => $exam->meta_title,
            'meta_description' => $exam->meta_description,
            'meta_keywords' => $exam->meta_keywords,
            'slug' => $exam->slug,
            'canonical_url' => $exam->canonical_url,
            'og_title' => $exam->og_title,
            'og_description' => $exam->og_description,
            'ai_generated' => (bool) $exam->ai_generated,
            'ai_improve' => (bool) $exam->ai_improve,
        ];
    @endphp
    <script>
        window.examCreateConfig = {
            options: @json($formOptions),
            endpoints: {
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
        // Full exam snapshot used by exam-create.js to hydrate the wizard for editing.
        window.examFormConfig = @json($examConfig);
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
