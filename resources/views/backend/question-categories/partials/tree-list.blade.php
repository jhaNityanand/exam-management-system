@php
    $levelColors     = ['#4f46e5', '#0f766e', '#d97706', '#dc2626', '#7c3aed', '#2563eb'];
    $levelSoftColors = ['#e0e7ff', '#dff6f3', '#ffedd5', '#ffe4e6', '#ede9fe', '#dbeafe'];
@endphp

@if ($categories->isEmpty())
    {{-- Empty state --}}
    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No categories found</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            No categories match your search filters or none exist yet.
        </p>
        <a href="{{ route('admin.questions.categories.create') }}"
           class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
            Create Category
        </a>
    </div>
@else

    <ul id="category-tree-root" class="space-y-4">
        @foreach ($categories as $category)
            @include('backend.question-categories.partials.tree-node', [
                'node'            => $category,
                'level'           => 0,
                'levelColors'     => $levelColors,
                'levelSoftColors' => $levelSoftColors,
            ])
        @endforeach
    </ul>

    {{-- Empty search state --}}
    <div id="category-empty-state" class="hidden rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center mt-4 dark:border-slate-700 dark:bg-slate-900/40">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No matching categories</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try a different keyword.</p>
    </div>

@endif
