@extends('backend.layouts.app')

@section('title', 'Edit Exam Category')
@section('page-title', 'Edit Exam Category')
@section('content-container-class', 'max-w-2xl mx-auto')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exam Categories', 'url' => route('admin.exam-categories.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<x-page-card>
    <form action="#" method="POST" id="exam-category-edit-form" novalidate>
        @csrf
        
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                Edit Exam Category
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Update the details of the exam category.
            </p>
        </div>

        <div class="px-4 py-6 sm:px-6 space-y-6">
            <!-- Category Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" class="panel-input w-full" value="University Admissions" required>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                <textarea id="description" name="description" rows="4" class="panel-input w-full">Standardized tests for undergraduate university admissions.</textarea>
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
                Save Changes
            </button>
        </div>
    </form>
</x-page-card>
@endsection
