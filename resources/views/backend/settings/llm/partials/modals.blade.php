<!-- Add / Edit LLM Account Medium-Width Modal -->
<div id="accountModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 flex items-center justify-center transition-all">
    <div class="relative w-full max-w-xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div>
                <h3 id="modalTitle" class="text-base font-bold text-slate-900 dark:text-white">Add LLM Provider Account</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure provider credentials, priorities, and routing options.</p>
            </div>
            <button type="button" onclick="closeAccountModal()" class="rounded-xl p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Form Validation Alert Banner -->
        <div id="formAlertBanner" class="mx-6 mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-medium hidden flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span id="formAlertText">Please fix the errors below before submitting.</span>
        </div>

        <!-- Modal Form Body (HTML novalidate to allow JS validation) -->
        <form id="accountForm" onsubmit="submitAccountForm(event)" novalidate class="p-6 space-y-4 max-h-[82vh] overflow-y-auto">
            <input type="hidden" id="accountId" name="id" value="" />

            <!-- Provider & Account Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="formProvider" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Provider <span class="text-rose-500">*</span></label>
                    <select id="formProvider" name="provider" onchange="onProviderChange(this.value); clearFieldError('provider')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-sm">
                        <option value="mistral">Mistral AI (Default Priority 1)</option>
                        <option value="groq">Groq (Priority 2)</option>
                        <option value="gemini">Google Gemini (Priority 3)</option>
                        <option value="openrouter">OpenRouter (Priority 4)</option>
                    </select>
                    <p data-error-for="provider" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>

                <div>
                    <label for="formAccountName" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Account Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="formAccountName" name="account_name" placeholder="e.g. Mistral Account 1" oninput="clearFieldError('account_name')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-sm" />
                    <p data-error-for="account_name" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>
            </div>

            <!-- Model & Priority -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="formModel" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Model Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="formModel" name="model" placeholder="e.g. mistral-small-latest" oninput="clearFieldError('model')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-sm font-mono" />
                    <p data-error-for="model" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>

                <div>
                    <label for="formPriority" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Account Priority <span class="text-rose-500">*</span></label>
                    <input type="number" id="formPriority" name="priority" value="1" min="1" oninput="clearFieldError('priority')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-sm" />
                    <p data-error-for="priority" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>
            </div>

            <!-- API Key -->
            <div>
                <label for="formApiKey" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">API Key <span class="text-rose-500">*</span></label>
                <input type="password" id="formApiKey" name="api_key" placeholder="Enter provider API key" oninput="clearFieldError('api_key')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-sm font-mono" />
                <p id="apiKeyHelp" class="text-[11px] text-slate-400 mt-1 hidden">Leave blank to keep existing key.</p>
                <p data-error-for="api_key" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
            </div>

            <!-- Base URL & Org ID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="formBaseUrl" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Custom Base URL (Optional)</label>
                    <input type="url" id="formBaseUrl" name="base_url" placeholder="https://api.mistral.ai/v1" oninput="clearFieldError('base_url')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs" />
                    <p data-error-for="base_url" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>

                <div>
                    <label for="formOrganizationId" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Organization ID (Optional)</label>
                    <input type="text" id="formOrganizationId" name="organization_id" placeholder="org-12345" oninput="clearFieldError('organization_id')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs" />
                    <p data-error-for="organization_id" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>
            </div>

            <!-- Daily Limits -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="formDailyReqLimit" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Daily Request Limit (Optional)</label>
                    <input type="number" id="formDailyReqLimit" name="daily_request_limit" placeholder="e.g. 1000" min="1" oninput="clearFieldError('daily_request_limit')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs" />
                    <p data-error-for="daily_request_limit" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>

                <div>
                    <label for="formDailyTokenLimit" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Daily Token Limit (Optional)</label>
                    <input type="number" id="formDailyTokenLimit" name="daily_token_limit" placeholder="e.g. 500000" min="1" oninput="clearFieldError('daily_token_limit')" class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs" />
                    <p data-error-for="daily_token_limit" class="text-xs text-rose-500 font-semibold mt-1 hidden"></p>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="formNotes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Notes (Optional)</label>
                <textarea id="formNotes" name="notes" rows="2" placeholder="Optional notes or usage details..." class="panel-input border-0 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs"></textarea>
            </div>

            <!-- Active Checkbox -->
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="formIsActive" name="is_active" value="1" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 dark:bg-slate-800" />
                <label for="formIsActive" class="text-xs font-semibold text-slate-800 dark:text-slate-200">Active and available for routing</label>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeAccountModal()" class="rounded-xl border border-slate-200 dark:border-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Cancel</button>
                <button type="submit" id="saveAccountBtn" class="rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 text-xs font-semibold shadow-sm transition">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Processed Record IDs Modal -->
<div id="recordIdsModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 flex items-center justify-center">
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Processed Record IDs</h3>
            <button type="button" onclick="closeRecordIdsModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="recordIdsContent" class="font-mono text-xs p-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 max-h-52 overflow-y-auto break-all"></div>

        <div class="flex justify-end">
            <button type="button" onclick="closeRecordIdsModal()" class="rounded-xl bg-slate-900 dark:bg-slate-800 text-white px-4 py-1.5 text-xs font-semibold">Close</button>
        </div>
    </div>
</div>
