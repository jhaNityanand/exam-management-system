<div class="px-4 py-5 sm:p-6">
    <form id="ads-custom-code-form" class="space-y-6" novalidate>
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Global advertisement code</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Paste scripts that should load site-wide. These will be rendered on the frontend in a later phase.
            </p>
        </div>

        <div class="ads-code-grid">
            <section class="ads-code-card">
                <div class="ads-code-card__head">
                    <h3 class="ads-code-card__title">Header code</h3>
                    <p class="ads-code-card__hint">Injected inside the <code>&lt;head&gt;</code> section (e.g. AdSense loader, verification tags).</p>
                </div>
                <label for="ads_header_code" class="sr-only">Header code</label>
                <textarea
                    id="ads_header_code"
                    name="header_code"
                    rows="12"
                    class="panel-input ads-code-card__textarea font-mono text-xs sm:text-sm"
                    placeholder="<!-- Paste header advertisement scripts here -->"
                >{{ $customCode['header_code'] ?? '' }}</textarea>
                <p class="qcat-field-error" data-error-for="header_code" hidden></p>
            </section>

            <section class="ads-code-card">
                <div class="ads-code-card__head">
                    <h3 class="ads-code-card__title">Footer code</h3>
                    <p class="ads-code-card__hint">Injected before the closing <code>&lt;/body&gt;</code> tag (e.g. delayed ad scripts).</p>
                </div>
                <label for="ads_footer_code" class="sr-only">Footer code</label>
                <textarea
                    id="ads_footer_code"
                    name="footer_code"
                    rows="12"
                    class="panel-input ads-code-card__textarea font-mono text-xs sm:text-sm"
                    placeholder="<!-- Paste footer advertisement scripts here -->"
                >{{ $customCode['footer_code'] ?? '' }}</textarea>
                <p class="qcat-field-error" data-error-for="footer_code" hidden></p>
            </section>
        </div>

        <div class="flex justify-end">
            <button type="submit" id="ads-custom-code-save" class="panel-button-primary text-sm">
                Save custom code
            </button>
        </div>
    </form>
</div>
