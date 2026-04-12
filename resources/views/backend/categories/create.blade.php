@extends('backend.layouts.app')

@section('title', 'New Category')
@section('page-title', 'Create Category Hierarchy')

@section('breadcrumbs')
    <li class="inline-flex items-center">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition">Admin</a>
    </li>
    <li>
        <div class="flex items-center">
            <span class="mx-2">/</span>
            <a href="{{ route('admin.categories.index') }}" class="hover:text-slate-900 dark:hover:text-white transition">Categories</a>
        </div>
    </li>
    <li>
        <div class="flex items-center">
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Create</span>
        </div>
    </li>
@endsection

@section('content')
<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 max-w-4xl">
    <div class="mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-1">Create Hierarchical Categories</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Add a main category and define its subcategories dynamically.</p>
    </div>

    {{-- The hierarchical form container --}}
    <form action="#" method="POST" id="category-hierarchy-form">
        @csrf
        
        <div id="hierarchy-container" class="space-y-4">
            <!-- Root Row will be injected here by JS -->
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">Cancel</a>
            <button type="button" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">Save Hierarchy</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('hierarchy-container');
        
        // Counter for unique IDs
        let counter = 0;

        function createRow(level = 0, parentId = null) {
            const id = `node-${counter++}`;
            const row = document.createElement('div');
            row.id = id;
            row.className = 'relative flex items-start gap-3 w-full';
            row.dataset.level = level;
            
            // Indentation
            const paddingLeft = level * 36;
            
            // Connection Indicator (Elbow Arrow)
            let connectionSvg = '';
            if (level > 0) {
                connectionSvg = `
                    <div class="absolute w-8 border-b-2 border-l-2 border-slate-200 dark:border-slate-700 rounded-bl-lg" style="left: ${paddingLeft - 28}px; top: -16px; height: 38px;"></div>
                `;
            }

            row.innerHTML = `
                <div class="w-full relative" style="padding-left: ${paddingLeft}px;">
                    ${connectionSvg}
                    <div class="group flex flex-col sm:flex-row items-center gap-3 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition">
                        
                        <div class="flex-1 w-full flex flex-col sm:flex-row gap-3">
                            <input type="text" placeholder="Category Name" class="w-full sm:w-1/3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <input type="text" placeholder="Short Description..." class="w-full sm:w-2/3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <div class="flex items-center gap-2 mt-2 sm:mt-0 w-full sm:w-auto justify-end">
                            <button type="button" class="add-child-btn px-3 py-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-xs font-semibold flex items-center gap-1 transition" data-id="${id}" data-level="${level}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Child
                            </button>
                            ${level > 0 ? `
                            <button type="button" class="remove-row-btn p-2 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition" data-id="${id}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            return row;
        }

        // Initialize Root
        const rootRow = createRow(0);
        container.appendChild(rootRow);

        // Event Delegation for Adding/Removing
        container.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.add-child-btn');
            if (addBtn) {
                const parentId = addBtn.dataset.id;
                const parentLevel = parseInt(addBtn.dataset.level, 10);
                const parentRow = document.getElementById(parentId);
                
                const newRow = createRow(parentLevel + 1, parentId);
                
                // Find where to insert (after the parent and all its existing descendants)
                let nextSibling = parentRow.nextElementSibling;
                while(nextSibling && parseInt(nextSibling.dataset.level, 10) > parentLevel) {
                    nextSibling = nextSibling.nextElementSibling;
                }
                
                if (nextSibling) {
                    container.insertBefore(newRow, nextSibling);
                } else {
                    container.appendChild(newRow);
                }
            }

            const removeBtn = e.target.closest('.remove-row-btn');
            if (removeBtn) {
                const rowId = removeBtn.dataset.id;
                const row = document.getElementById(rowId);
                const level = parseInt(row.dataset.level, 10);
                
                // Also remove all descendants
                let nextSibling = row.nextElementSibling;
                const toRemove = [row];
                while(nextSibling && parseInt(nextSibling.dataset.level, 10) > level) {
                    toRemove.push(nextSibling);
                    nextSibling = nextSibling.nextElementSibling;
                }
                
                toRemove.forEach(el => el.remove());
            }
        });
    });
</script>
@endpush
