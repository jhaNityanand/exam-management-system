@extends('backend.layouts.app')

@section('title', 'Edit Candidate')
@section('page-title', 'Edit Candidate')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Candidates', 'url' => route('admin.candidates.index')],
        ['label' => $candidate->name, 'url' => route('admin.candidates.show', $candidate)],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<div class="w-full relative">
    <x-page-card class="category-builder-card overflow-visible relative z-10 w-full">
        <form action="{{ route('admin.candidates.update', $candidate) }}" method="POST" id="candidate-form" enctype="multipart/form-data" class="category-builder" novalidate>
            @csrf
            @method('PUT')

            <div class="category-builder__header px-4 py-6 sm:px-6">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Edit Candidate</h1>
                    <p class="category-builder__subtitle text-slate-500">
                        Update personal details, verification status, and account settings for {{ $candidate->name }}.
                    </p>
                </div>
            </div>

            @include('backend.candidates.partials.form', ['candidate' => $candidate, 'avatarUrl' => $avatarUrl])

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('admin.candidates.show', $candidate) }}" class="panel-button-secondary text-center">Cancel</a>
                <button type="submit" class="panel-button-primary" id="btn-submit">Save Changes</button>
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
            isEdit: true,
            existingAvatarUrl: @json($avatarUrl),
            initials: @json(user_initials($candidate->name, 'CA')),
            avatarColor: @json(user_avatar($candidate)['color']),
        };
    </script>
    <script src="{{ asset('js/components/user-avatar.js') }}?v={{ filemtime(public_path('js/components/user-avatar.js')) }}"></script>
    <script src="{{ asset('js/components/dob-datepicker.js') }}?v={{ filemtime(public_path('js/components/dob-datepicker.js')) }}"></script>
    <script src="{{ asset('js/backend/candidate-form.js') }}?v={{ filemtime(public_path('js/backend/candidate-form.js')) }}"></script>
@endpush
