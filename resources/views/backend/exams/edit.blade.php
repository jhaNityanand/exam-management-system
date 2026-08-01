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
    <link rel="stylesheet" href="{{ asset('css/modules/form-utils.css') }}?v={{ filemtime(public_path('css/modules/form-utils.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-create.css') }}?v={{ filemtime(public_path('css/backend/exam-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/question-bank-accordion.css') }}?v={{ filemtime(public_path('css/question-bank-accordion.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}?v={{ filemtime(public_path('css/backend/question-category-form.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ filemtime(public_path('js/components/editor.js')) }}"></script>
    <script src="{{ asset('js/components/select.js') }}?v={{ filemtime(public_path('js/components/select.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}?v={{ filemtime(public_path('js/components/tom-select-blur.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ filemtime(public_path('js/components/tom-select-hierarchy.js')) }}"></script>
    <script src="{{ asset('js/components/question-bank-accordion.js') }}?v={{ filemtime(public_path('js/components/question-bank-accordion.js')) }}"></script>
    <script src="{{ asset('js/backend/question-bank-init.js') }}?v={{ filemtime(public_path('js/backend/question-bank-init.js')) }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}?v={{ filemtime(public_path('js/backend/seo-manager.js')) }}"></script>
    <script src="{{ asset('js/backend/slug-field.js') }}?v={{ filemtime(public_path('js/backend/slug-field.js')) }}"></script>
    <script src="{{ asset('js/core/form-utils.js') }}?v={{ filemtime(public_path('js/core/form-utils.js')) }}"></script>
    @php
        $examFormatValue = is_array($exam->exam_format)
            ? $exam->exam_format
            : (json_decode($exam->exam_format ?? '[]', true) ?: []);

        $exam->loadMissing(['parts' => function ($query) {
            $query->orderBy('sort_order')->with([
                'questions:id',
                'selectedQuestionCategories:id',
            ]);
        }]);

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

            // Timer
            'enable_exam_timer' => (bool) $exam->enable_exam_timer,
            'exam_duration_minutes' => $exam->duration,
            'auto_submit_on_timer_end' => (bool) $exam->auto_submit_on_timer_end,

            // Schedule & attempts
            'schedule_type' => $exam->schedule_type,
            'schedule_start_at' => optional($exam->scheduled_start)->format('Y-m-d H:i'),
            'schedule_end_at' => optional($exam->scheduled_end)->format('Y-m-d H:i'),
            'attempt_limit_type' => $exam->attempt_limit_type,
            'attempt_limit_count' => $exam->max_attempts,

            // Exam-level scoring gateways
            'passing_marks' => $exam->passing_marks,
            'enable_negative_marking' => (bool) $exam->enable_negative_marking,
            'negative_marking_type' => $exam->negative_marking_type,
            'negative_mark_per_question' => $exam->negative_mark_per_question,

            // Exam parts — each part carries its own configuration, rules, and question bank.
            'parts' => $exam->parts->map(function (\App\Models\ExamPart $part) {
                $jsonCategories = collect($part->selected_categories ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $pivotCategories = $part->relationLoaded('selectedQuestionCategories')
                    ? $part->selectedQuestionCategories->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                    : [];
                $selectedCategories = $jsonCategories !== [] ? $jsonCategories : $pivotCategories;

                $extraQuestionCategories = collect($part->extra_questions_categories ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                if ($extraQuestionCategories === [] && (bool) $part->fix_category_questions) {
                    $extraQuestionCategories = $selectedCategories;
                }

                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'is_default' => (bool) $part->is_default,
                    'total_questions' => $part->total_questions,
                    'total_marks' => $part->total_marks,
                    'use_question_pool' => (bool) $part->use_question_pool,
                    'maximum_questions' => $part->maximum_questions,
                    'fixed_questions' => (bool) $part->fixed_questions,
                    'fixed_paper_set' => (bool) $part->fixed_paper_set,
                    'paper_sets' => $part->paper_sets,
                    'fix_category_questions' => (bool) $part->fix_category_questions,
                    'fix_category_marks' => (bool) $part->fix_category_marks,
                    'distribution_type' => $part->distribution_type,
                    'fix_marks_each_question' => (bool) $part->fix_marks_each_question,
                    'selected_categories' => $selectedCategories,
                    'extra_questions_categories' => $extraQuestionCategories,
                    'extra_questions_allocations' => $part->extra_questions_allocations ?? [],
                    'extra_marks_allocations' => $part->extra_marks_allocations ?? [],
                    'question_marks_filter' => $part->question_marks_filter ?? [],
                    'category_question_rules' => $part->category_question_rules ?? [],
                    'shuffle_questions' => (bool) $part->shuffle_questions,
                    'shuffle_categories' => (bool) $part->shuffle_categories,
                    'shuffle_options' => (bool) $part->shuffle_options,
                    'question_ids' => $part->questions->pluck('id')->values()->all(),
                ];
            })->values()->all(),

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

            // Instructions & rules — preserve empty arrays (do not fall back to defaults)
            'predefined_instruction_rules' => $exam->predefined_instruction_rules ?? [],
            'focus_violation_limit' => (int) ($exam->proctoringPolicy?->focus_violation_limit ?? 3),

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
        window.slugResolveUrl = @json(route('admin.slug.resolve'));
        window.examCreateConfig = {
            options: @json($formOptions),
            endpoints: {
                categories: @json(route('admin.api.question-bank.categories')),
                questionBank: @json(route('admin.api.question-bank.questions')),
                questionBankCounts: @json(route('admin.api.question-bank.counts')),
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
            categorySelect?.on?.('change', () => window.examCreateUpdateSidebar?.());
            window.EmsTomSelectBlur?.blurNativeSelects(document.querySelector('form') || document);
            window.EmsSlugField?.bind({
                module: 'exam',
                sourceSelector: '#exam_title',
                slugSelector: '#meta-slug',
                resolveUrl: window.slugResolveUrl,
                ignoreId: @json($exam->id),
            });
        });
    </script>
    <script src="{{ asset('js/backend/exam-create.js') }}?v={{ filemtime(public_path('js/backend/exam-create.js')) }}"></script>
@endpush
