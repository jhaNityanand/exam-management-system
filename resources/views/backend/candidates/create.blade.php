@extends('backend.layouts.app')

@section('title', 'Create Candidate')
@section('page-title', 'Create Candidate')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Candidates', 'url' => route('admin.candidates.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<div class="w-full relative">
    <x-page-card class="category-builder-card overflow-visible relative z-10 w-full">
        <form action="{{ route('admin.candidates.store') }}" method="POST" id="candidate-form" enctype="multipart/form-data" class="category-builder" novalidate>
            @csrf

            <div class="category-builder__header px-4 py-6 sm:px-6">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Create Candidate</h1>
                    <p class="category-builder__subtitle text-slate-500">
                        Add a new candidate with personal details, contact info, and account settings.
                    </p>
                </div>
            </div>

            @include('backend.candidates.partials.form', ['candidate' => null, 'avatarUrl' => null])

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.candidates.index') }}" class="panel-button-secondary text-center">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Create Candidate</button>
            </div>
        </form>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/category-manager.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/dob-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/candidate-form.css') }}?v={{ filemtime(public_path('css/backend/candidate-form.css')) }}">
@endpush

@push('scripts')
    <script>
        window.candidateFormConfig = {
            isEdit: false,
            existingAvatarUrl: null,
            initials: @json(user_initials('Candidate', 'CA')),
            avatarColor: @json(\App\Support\UserAvatar::color('candidate')),
        };
    </script>
    <script src="{{ asset('js/components/user-avatar.js') }}?v={{ filemtime(public_path('js/components/user-avatar.js')) }}"></script>
    <script src="{{ asset('js/components/dob-datepicker.js') }}?v={{ filemtime(public_path('js/components/dob-datepicker.js')) }}"></script>
    <script src="{{ asset('js/backend/candidate-form.js') }}?v={{ filemtime(public_path('js/backend/candidate-form.js')) }}"></script>
@endpush
