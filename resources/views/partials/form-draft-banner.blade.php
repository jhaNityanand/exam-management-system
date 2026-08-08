<div id="ems-draft-banner"
     class="mb-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200"
     data-draft-banner
     hidden>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold">Unsaved Draft Available</p>
                <p class="text-xs text-amber-700 dark:text-amber-300">We found an unsaved draft for this form from <span data-draft-time class="font-medium"></span>.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    data-draft-restore-btn
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/40 transition">
                Restore Draft
            </button>
            <button type="button"
                    data-draft-discard-btn
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition">
                Discard
            </button>
        </div>
    </div>
</div>
