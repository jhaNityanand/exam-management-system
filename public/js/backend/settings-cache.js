/**
 * Cache & Optimization — AJAX action runner.
 */
(function () {
    'use strict';

    const config = window.cacheOptimizationConfig || {};
    if (!config.runUrl) return;

    const results = document.getElementById('cache-results');
    const empty = document.getElementById('cache-results-empty');
    const clearBtn = document.getElementById('cache-results-clear');

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const prependResult = (result, message) => {
        if (!results) return;
        empty?.setAttribute('hidden', 'hidden');
        results.hidden = false;
        clearBtn?.removeAttribute('hidden');

        const ok = Boolean(result?.success);
        const card = document.createElement('div');
        card.className = 'rounded-xl border p-4 ' + (ok
            ? 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/30 dark:bg-emerald-500/10'
            : 'border-red-200 bg-red-50/80 dark:border-red-500/30 dark:bg-red-500/10');
        card.innerHTML = `
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold ${ok ? 'text-emerald-800 dark:text-emerald-300' : 'text-red-800 dark:text-red-300'}">
                    ${escapeHtml(result?.label || 'Action')} — ${ok ? 'Success' : 'Failed'}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    ${escapeHtml(String(result?.duration_ms ?? 0))} ms · exit ${escapeHtml(String(result?.exit_code ?? '—'))}
                </p>
            </div>
            <p class="mt-1 text-xs ${ok ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}">${escapeHtml(message || '')}</p>
            <pre class="mt-3 max-h-48 overflow-auto rounded-lg bg-slate-950/90 text-slate-100 text-xs p-3 whitespace-pre-wrap break-words">${escapeHtml(result?.output || '(no output)')}</pre>
        `;
        results.prepend(card);
    };

    clearBtn?.addEventListener('click', () => {
        if (results) results.innerHTML = '';
        results.hidden = true;
        empty?.removeAttribute('hidden');
        clearBtn.setAttribute('hidden', 'hidden');
    });

    document.querySelectorAll('.cache-action-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const action = btn.getAttribute('data-action');
            const label = btn.getAttribute('data-label') || action;
            const confirmText = btn.getAttribute('data-confirm') || '';
            const danger = btn.getAttribute('data-danger') === '1';

            if (confirmText) {
                const confirm = await window.Swal?.fire?.({
                    icon: danger ? 'warning' : 'question',
                    title: label,
                    text: confirmText,
                    showCancelButton: true,
                    confirmButtonText: danger ? 'Yes, continue' : 'Run',
                    confirmButtonColor: danger ? '#dc2626' : undefined,
                });
                if (confirm && !confirm.isConfirmed) return;
            }

            const original = btn.querySelector('.cache-action-btn__label')?.textContent || btn.textContent;
            const labelEl = btn.querySelector('.cache-action-btn__label');
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-wait');
            if (labelEl) labelEl.textContent = 'Running…';
            else btn.textContent = 'Running…';

            try {
                const response = await fetch(config.runUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ action }),
                });
                const data = await response.json().catch(() => ({}));
                const result = data.result || {
                    success: response.ok,
                    label,
                    output: data.message || 'No response body.',
                    exit_code: response.ok ? 0 : 1,
                    duration_ms: 0,
                };

                prependResult(result, data.message);
                if (result.success) {
                    window.EmsToast?.success?.(data.message || `${label} completed.`);
                } else {
                    window.EmsToast?.error?.(data.message || `${label} failed.`);
                    window.Swal?.fire?.({
                        icon: 'error',
                        title: `${label} failed`,
                        text: result.output || data.message || 'Command failed.',
                    });
                }
            } catch (error) {
                prependResult({
                    success: false,
                    label,
                    output: error.message || 'Request failed.',
                    exit_code: 1,
                    duration_ms: 0,
                }, 'Request failed.');
                window.Swal?.fire?.({
                    icon: 'error',
                    title: 'Request failed',
                    text: error.message || 'Could not run the optimization action.',
                });
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-wait');
                if (labelEl) labelEl.textContent = original || 'Run';
                else btn.textContent = original || 'Run';
            }
        });
    });
}());
