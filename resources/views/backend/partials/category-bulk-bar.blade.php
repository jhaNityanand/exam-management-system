<div id="category-bulk-bar" class="list-bulk-bar" hidden>
    <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <input type="checkbox" id="category-select-all" class="list-select-all">
            <span><span id="category-selected-count">0</span> selected</span>
        </label>
        <div id="category-bulk-actions-active" class="flex flex-wrap items-center gap-2">
            <button type="button" id="category-bulk-delete" class="list-bulk-btn list-bulk-btn--danger">Move to Bin</button>
            <select id="category-bulk-status" class="panel-input w-40 text-sm">
                <option value="">Update Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        <div id="category-bulk-actions-bin" hidden>
            <button type="button" id="category-bulk-restore" class="list-bulk-btn">Restore</button>
        </div>
    </div>
</div>

<form id="category-bulk-delete-form" action="{{ $bulkDeleteRoute }}" method="POST" class="hidden">@csrf</form>
<form id="category-bulk-restore-form" action="{{ $bulkRestoreRoute }}" method="POST" class="hidden">@csrf</form>
<form id="category-bulk-status-form" action="{{ $bulkStatusRoute }}" method="POST" class="hidden">@csrf @method('PATCH')<input type="hidden" name="status"></form>
<form id="category-restore-form" action="" method="POST" class="hidden">@csrf @method('PATCH')</form>
