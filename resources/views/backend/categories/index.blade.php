@extends('backend.layouts.app')

@section('title', 'Categories')
@section('page-title', 'Category List')


@section('breadcrumbs')
    <li class="inline-flex items-center">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition">Admin</a>
    </li>
    <li>
        <div class="flex items-center">
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Categories</span>
        </div>
    </li>
@endsection

@section('content')

@php
// Dummy Data
$categories = [
    [
        'id' => 1, 'name' => 'Science', 'description' => 'All science related topics including physics, chemistry, and biology. This description is intentionally a little long to test the truncation and the view more button.',
        'children' => [
            ['id' => 2, 'name' => 'Physics', 'description' => 'Physics topics', 'children' => [
                ['id' => 3, 'name' => 'Classical Mechanics', 'description' => 'Newtonian mechanics', 'children' => []],
                ['id' => 4, 'name' => 'Quantum Physics', 'description' => 'Modern physics', 'children' => []]
            ]],
            ['id' => 5, 'name' => 'Biology', 'description' => 'Study of life', 'children' => []]
        ]
    ],
    [
        'id' => 6, 'name' => 'Mathematics', 'description' => 'Math topics including algebra and geometry.', 'children' => [
            ['id' => 7, 'name' => 'Algebra', 'description' => '', 'children' => []]
        ]
    ]
];
@endphp

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center sm:flex-row flex-col gap-4 sm:gap-0">
        <h3 class="font-semibold text-slate-900 dark:text-white text-lg">Category Tree</h3>
        <div class="flex items-center gap-3">
            <button id="expand-all-btn" class="text-sm font-medium text-sky-600 dark:text-sky-400 hover:text-sky-700 transition px-3 py-2 rounded-lg hover:bg-sky-50 dark:hover:bg-sky-900/30">Expand All</button>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Category
            </a>
        </div>
    </div>
    
    <div class="px-6 py-4" id="category-tree-container">
        {{-- Recursive Blade View equivalent --}}
        <ul class="space-y-2 category-tree-root">
            @foreach($categories as $category)
                @include('backend.categories.partials.tree-node', ['node' => $category, 'level' => 0])
            @endforeach
        </ul>
    </div>
</div>

{{-- Modal for Description --}}
<div id="desc-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 w-full max-w-lg mx-4">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <h3 id="desc-modal-title" class="font-semibold text-slate-900 dark:text-white">Category Name</h3>
            <button id="close-desc-modal" class="text-slate-400 hover:text-slate-500 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-6 text-sm text-slate-600 dark:text-slate-300" id="desc-modal-content">
            <!-- content -->
        </div>
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-b-2xl border-t border-slate-100 dark:border-slate-800 flex justify-end">
            <button id="close-desc-modal-btn" class="px-4 py-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition text-slate-700 dark:text-slate-300">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle Nodes
        document.querySelectorAll('.toggle-node-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const li = e.currentTarget.closest('li.tree-node');
                const childrenContainer = li.querySelector(':scope > ul');
                const icon = e.currentTarget.querySelector('svg');
                
                if (childrenContainer) {
                    childrenContainer.classList.toggle('hidden');
                    icon.classList.toggle('rotate-90');
                }
            });
        });

        // Expand All
        let expanded = false;
        document.getElementById('expand-all-btn').addEventListener('click', (e) => {
            expanded = !expanded;
            e.target.textContent = expanded ? 'Collapse All' : 'Expand All';
            
            document.querySelectorAll('li.tree-node > ul').forEach(ul => {
                if (expanded) ul.classList.remove('hidden');
                else ul.classList.add('hidden');
            });
            
            document.querySelectorAll('.toggle-node-btn svg').forEach(svg => {
                if (expanded) svg.classList.add('rotate-90');
                else svg.classList.remove('rotate-90');
            });
        });

        // View Description Modal
        const modal = document.getElementById('desc-modal');
        const modalTitle = document.getElementById('desc-modal-title');
        const modalContent = document.getElementById('desc-modal-content');
        
        const closeModal = () => modal.classList.add('hidden');
        
        document.getElementById('close-desc-modal').addEventListener('click', closeModal);
        document.getElementById('close-desc-modal-btn').addEventListener('click', closeModal);
        
        document.querySelectorAll('.view-desc-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const name = btn.dataset.name;
                const desc = btn.dataset.desc;
                modalTitle.textContent = name;
                modalContent.textContent = desc;
                modal.classList.remove('hidden');
            });
        });
    });
</script>
@endpush
