@extends('backend.layouts.app')

@section('title', 'Create Exam Category')
@section('page-title', 'Create Exam Category')
@section('content-container-class', 'max-w-2xl mx-auto')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exam Categories', 'url' => route('admin.exam-categories.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<x-page-card>
    <form action="#" method="POST" id="exam-category-create-form" novalidate>
        @csrf
        
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                New Exam Category
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Create a new top-level exam category to group similar exams together.
            </p>
        </div>

        <div class="px-4 py-6 sm:px-6 space-y-6">
            <!-- Category Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" class="panel-input w-full" placeholder="e.g. University Admissions" required>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                <textarea id="description" name="description" rows="4" class="panel-input w-full" placeholder="Describe the purpose of this exam category..."></textarea>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">A brief description will help administrators understand the category's scope.</p>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Status <span class="text-rose-500">*</span></label>
                <select id="status" name="status" class="panel-input w-full">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Inactive categories will not appear as options when creating new exams.</p>
            </div>
        </div>

        <div class="bg-slate-50 px-4 py-4 sm:px-6 dark:bg-slate-900/50 flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-200/80 dark:border-slate-800">
            <a href="{{ route('admin.exam-categories.index') }}" class="panel-button-secondary">
                Cancel
            </a>
            <button type="button" class="panel-button-primary" onclick="window.location.href='{{ route('admin.exam-categories.index') }}'">
                Save Category
            </button>
        </div>
    </form>
</x-page-card>
@endsection
