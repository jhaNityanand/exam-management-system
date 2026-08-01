<div id="category-bulk-bar" class="list-bulk-bar" hidden>
    <div class="list-bulk-bar__inner">
        <div class="list-bulk-bar__meta">
            <label class="list-bulk-bar__select-all">
                <input type="checkbox" id="category-select-all" class="list-select-all" aria-label="Select all categories">
                <span class="list-bulk-bar__badge" aria-live="polite">
                    <span class="list-bulk-bar__count" id="category-selected-count">0</span>
                    <span class="list-bulk-bar__label">selected</span>
                </span>
            </label>
        </div>
        <div class="list-bulk-bar__actions">
            <div id="category-bulk-actions-active" class="list-bulk-bar__group">
                <button type="button" id="category-bulk-delete" class="list-bulk-btn list-bulk-btn--danger">Move to Bin</button>
                <select id="category-bulk-status" class="panel-input text-sm" data-no-search aria-label="New status">
                    <option value="">Update Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div id="category-bulk-actions-bin" class="list-bulk-bar__group" hidden>
                <button type="button" id="category-bulk-restore" class="list-bulk-btn">Restore</button>
            </div>
        </div>
        <div class="list-bulk-bar__aside">
            <button type="button" class="list-bulk-btn list-bulk-btn--ghost" data-list-clear-selection>Clear selection</button>
        </div>
    </div>
</div>

<form id="category-bulk-delete-form" action="{{ $bulkDeleteRoute }}" method="POST" class="hidden">@csrf</form>
<form id="category-bulk-restore-form" action="{{ $bulkRestoreRoute }}" method="POST" class="hidden">@csrf</form>
<form id="category-bulk-status-form" action="{{ $bulkStatusRoute }}" method="POST" class="hidden">@csrf @method('PATCH')<input type="hidden" name="status"></form>
<form id="category-restore-form" action="" method="POST" class="hidden">@csrf @method('PATCH')</form>
