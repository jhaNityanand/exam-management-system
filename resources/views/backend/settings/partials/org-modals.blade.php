{{-- Hero modal --}}
<div id="hero-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hero-modal-title">
    <div class="ems-dialog__backdrop" data-hero-modal-close></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="hero-form" class="ems-dialog__form">
            <header class="ems-dialog__header">
                <div class="min-w-0">
                    <h3 id="hero-modal-title" class="ems-dialog__title">Add hero banner</h3>
                    <p class="ems-dialog__subtitle">Configure slide content, CTAs, schedule, and images.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-hero-modal-close aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <div class="ems-dialog__body">
                <input type="hidden" id="hero_id" name="id" value="">

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Content</h4>
                    <div class="space-y-4">
                        <div>
                            <label for="hero_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" id="hero_title" name="title" required class="panel-input mt-1 block w-full" placeholder="Master every competitive exam">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="min-w-0">
                                <label for="hero_subtitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subtitle</label>
                                <input type="text" id="hero_subtitle" name="subtitle" class="panel-input mt-1 block w-full">
                            </div>
                            <div class="min-w-0">
                                <label for="hero_badge_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Badge text</label>
                                <input type="text" id="hero_badge_text" name="badge_text" class="panel-input mt-1 block w-full">
                            </div>
                        </div>
                        <div>
                            <label for="hero_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                            <textarea id="hero_description" name="description" rows="3" class="panel-input mt-1 block w-full"></textarea>
                        </div>
                    </div>
                </section>

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Call to action</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="min-w-0">
                            <label for="hero_primary_cta_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary CTA label</label>
                            <input type="text" id="hero_primary_cta_label" name="primary_cta_label" class="panel-input mt-1 block w-full">
                        </div>
                        <div class="min-w-0">
                            <label for="hero_primary_cta_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary CTA URL</label>
                            <input type="text" id="hero_primary_cta_url" name="primary_cta_url" class="panel-input mt-1 block w-full" placeholder="/exams">
                        </div>
                        <div class="min-w-0">
                            <label for="hero_secondary_cta_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary CTA label</label>
                            <input type="text" id="hero_secondary_cta_label" name="secondary_cta_label" class="panel-input mt-1 block w-full">
                        </div>
                        <div class="min-w-0">
                            <label for="hero_secondary_cta_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary CTA URL</label>
                            <input type="text" id="hero_secondary_cta_url" name="secondary_cta_url" class="panel-input mt-1 block w-full">
                        </div>
                    </div>
                </section>

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Schedule &amp; status</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="min-w-0">
                            <label for="hero_status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                            <select id="hero_status" name="status" class="panel-input mt-1 block w-full">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label for="hero_sort_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sort order</label>
                            <input type="number" id="hero_sort_order" name="sort_order" min="0" class="panel-input mt-1 block w-full" value="1">
                        </div>
                        <div class="min-w-0">
                            <x-date-time-picker name="starts_at" id="hero_starts_at" mode="datetime" label="Starts at" />
                        </div>
                        <div class="min-w-0">
                            <x-date-time-picker name="ends_at" id="hero_ends_at" mode="datetime" label="Ends at" />
                        </div>
                    </div>
                    <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" id="hero_show_search" name="show_search" value="1" class="rounded border-slate-300 text-indigo-600" checked>
                        Show search in hero
                    </label>
                </section>

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Images</h4>
                    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Desktop recommended <strong>1920×800</strong>. Mobile recommended <strong>1080×1350</strong>.</p>
                    <div class="hero-gallery-grid" id="hero-gallery-pickers">
                        @include('backend.partials.gallery-picker', [
                            'name' => 'image_id',
                            'label' => 'Desktop image',
                            'value' => null,
                            'kind' => 'image',
                        ])
                        @include('backend.partials.gallery-picker', [
                            'name' => 'mobile_image_id',
                            'label' => 'Mobile image',
                            'value' => null,
                            'kind' => 'image',
                        ])
                    </div>
                </section>
            </div>

            <footer class="ems-dialog__footer">
                <button type="button" class="panel-button-secondary" data-hero-modal-close>Cancel</button>
                <button type="submit" class="panel-button-primary" id="hero-save-btn">Save banner</button>
            </footer>
        </form>
    </div>
</div>

{{-- FAQ modal --}}
<div id="faq-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="faq-modal-title">
    <div class="ems-dialog__backdrop" data-faq-modal-close></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="faq-form" class="ems-dialog__form">

            {{-- Redesigned header with icon --}}
            <header class="ems-dialog__header faq-modal-header">
                <div class="faq-modal-header__icon" aria-hidden="true">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="faq-modal-title" class="ems-dialog__title">Add FAQ</h3>
                    <p class="ems-dialog__subtitle">Shown on the public homepage when status is active.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-faq-modal-close aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <div class="ems-dialog__body faq-modal-body">
                <input type="hidden" id="faq_id" name="id" value="">

                {{-- Content section --}}
                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-.1em;margin-right:.3rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        Content
                    </h4>
                    <div class="faq-field-group">

                        {{-- Question --}}
                        <div class="faq-field">
                            <div class="faq-field__label-row">
                                <label for="faq_question" class="faq-field__label">
                                    Question <span class="faq-field__req">*</span>
                                </label>
                                <span class="faq-field__count" id="faq_question_count">0 / 500</span>
                            </div>
                            <input type="text" id="faq_question" name="question" required maxlength="500"
                                   class="faq-field__input"
                                   placeholder="e.g. How do I reset my password?"
                                   oninput="document.getElementById('faq_question_count').textContent = this.value.length + ' / 500'">
                            <p class="qcat-field-error" data-error-for="question" hidden></p>
                        </div>

                        {{-- Answer --}}
                        <div class="faq-field">
                            <div class="faq-field__label-row">
                                <label for="faq_answer" class="faq-field__label">
                                    Answer <span class="faq-field__req">*</span>
                                </label>
                                <span class="faq-field__count" id="faq_answer_count">0 / 10 000</span>
                            </div>
                            <textarea id="faq_answer" name="answer" required rows="5" maxlength="10000"
                                      class="faq-field__input faq-field__textarea"
                                      placeholder="Write a clear, helpful answer that guides the user…"
                                      oninput="document.getElementById('faq_answer_count').textContent = this.value.length.toLocaleString() + ' / 10 000'"></textarea>
                            <p class="qcat-field-error" data-error-for="answer" hidden></p>
                        </div>

                    </div>
                </section>

                {{-- Settings section --}}
                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-.1em;margin-right:.3rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                        Settings
                    </h4>
                    <div class="faq-meta-grid">

                        <div class="faq-field">
                            <label for="faq_category_id" class="faq-field__label">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-.1em;margin-right:.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                Category
                            </label>
                            <select id="faq_category_id" name="faq_category_id" class="faq-field__select">
                                <option value="">Uncategorized</option>
                                @foreach($faqCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="faq-field">
                            <label for="faq_status" class="faq-field__label">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-.1em;margin-right:.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Status <span class="faq-field__req">*</span>
                            </label>
                            <select id="faq_status" name="status" required class="faq-field__select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="faq-field">
                            <label for="faq_sort_order" class="faq-field__label">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-.1em;margin-right:.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                                Sort order
                            </label>
                            <input type="number" id="faq_sort_order" name="sort_order" min="0" max="9999" value="0" class="faq-field__input faq-field__number">
                        </div>

                        <div class="faq-field">
                            <label class="faq-featured-toggle" for="faq_is_featured">
                                <input type="checkbox" id="faq_is_featured" name="is_featured" value="1" class="faq-featured-toggle__input">
                                <span class="faq-featured-toggle__track" aria-hidden="true"></span>
                                <span class="faq-featured-toggle__text">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="color:#f59e0b;flex-shrink:0;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    Featured
                                </span>
                            </label>
                            <p class="faq-featured-toggle__hint">Featured FAQs appear in the highlighted homepage section.</p>
                        </div>

                    </div>
                </section>

                {{-- Tip --}}
                <div class="faq-modal-tip" role="note">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;color:#6366f1;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>Keep answers concise (2–3 sentences). Use <strong>Sort order</strong> to control display sequence. Only <strong>Active</strong> entries appear publicly.</p>
                </div>
            </div>

            <footer class="ems-dialog__footer">
                <button type="button" class="faq-modal-btn faq-modal-btn--cancel" data-faq-modal-close>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancel
                </button>
                <button type="submit" class="faq-modal-btn faq-modal-btn--save" id="faq-save-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save FAQ
                </button>
            </footer>
        </form>
    </div>
</div>

{{-- FAQ modal styles --}}
<style>
    /* ── Modal header icon ── */
    .faq-modal-header {
        gap: 0.85rem;
        align-items: flex-start;
        background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
        border-bottom-color: #e0e7ff;
    }
    .dark .faq-modal-header {
        background: linear-gradient(135deg, rgb(15 23 42 / 0.95) 0%, rgb(49 46 129 / 0.22) 100%);
        border-bottom-color: rgb(99 102 241 / 0.2);
    }
    .faq-modal-header__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        background: #4f46e5;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgb(79 70 229 / 0.3);
    }

    /* ── Modal body ── */
    .faq-modal-body { padding-bottom: 0; }

    /* ── Field group ── */
    .faq-field-group {
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }
    .faq-field { min-width: 0; }
    .faq-field__label-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        margin-bottom: 0.45rem;
    }
    .faq-field__label {
        font-size: 0.825rem;
        font-weight: 600;
        color: #334155;
    }
    .dark .faq-field__label { color: #e2e8f0; }
    .faq-field__req { color: #f43f5e; margin-left: .1rem; }
    .faq-field__count {
        font-size: 0.7rem;
        font-weight: 500;
        color: #94a3b8;
        font-variant-numeric: tabular-nums;
    }
    .dark .faq-field__count { color: #64748b; }
    .faq-field__input,
    .faq-field__select {
        width: 100%;
        min-height: 2.75rem;
        padding: 0.6rem 0.85rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        background: #fff;
        color: #0f172a;
        font-size: 0.875rem;
        line-height: 1.5;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        appearance: auto;
    }
    .dark .faq-field__input,
    .dark .faq-field__select {
        border-color: #475569;
        background: #0f172a;
        color: #e2e8f0;
    }
    .faq-field__input:focus,
    .faq-field__select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
    }
    .faq-field__textarea {
        min-height: 8rem;
        resize: vertical;
    }
    .faq-field__number { max-width: 8rem; }

    /* ── Metadata grid ── */
    .faq-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    /* ── Featured toggle ── */
    .faq-featured-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        cursor: pointer;
        user-select: none;
        padding: 0.6rem 0.85rem;
        border-radius: 0.75rem;
        background: #f8fafc;
        transition: border-color 0.15s, background 0.15s;
    }
    .dark .faq-featured-toggle {
        border-color: #334155;
        background: rgb(15 23 42 / 0.45);
    }
    .faq-featured-toggle:has(.faq-featured-toggle__input:checked) {
        border-color: #fcd34d;
        background: #fffbeb;
    }
    .dark .faq-featured-toggle:has(.faq-featured-toggle__input:checked) {
        border-color: rgb(251 191 36 / 0.45);
        background: rgb(245 158 11 / 0.1);
    }
    .faq-featured-toggle__input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .faq-featured-toggle__track {
        position: relative;
        display: inline-block;
        width: 2.5rem;
        height: 1.4rem;
        border-radius: 999px;
        background: #cbd5e1;
        flex-shrink: 0;
        transition: background 0.2s;
    }
    .dark .faq-featured-toggle__track { background: #475569; }
    .faq-featured-toggle__track::after {
        content: '';
        position: absolute;
        top: 0.2rem;
        left: 0.2rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
        transition: transform 0.2s;
    }
    .faq-featured-toggle__input:checked ~ .faq-featured-toggle__track {
        background: #f59e0b;
    }
    .faq-featured-toggle__input:checked ~ .faq-featured-toggle__track::after {
        transform: translateX(1.1rem);
    }
    .faq-featured-toggle__text {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #475569;
    }
    .dark .faq-featured-toggle__text { color: #94a3b8; }
    .faq-featured-toggle__input:checked ~ .faq-featured-toggle__text { color: #b45309; }
    .dark .faq-featured-toggle__input:checked ~ .faq-featured-toggle__text { color: #fcd34d; }
    .faq-featured-toggle__hint {
        margin: 0.4rem 0 0;
        font-size: 0.72rem;
        color: #94a3b8;
        line-height: 1.4;
    }
    .dark .faq-featured-toggle__hint { color: #64748b; }

    /* ── Tip block ── */
    .faq-modal-tip {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        margin: 1rem 0 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        font-size: 0.8rem;
        color: #4338ca;
        line-height: 1.5;
    }
    .dark .faq-modal-tip {
        background: rgb(99 102 241 / 0.1);
        border-color: rgb(99 102 241 / 0.25);
        color: #a5b4fc;
    }
    .faq-modal-tip p { margin: 0; }

    /* ── Footer buttons ── */
    .faq-modal-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2.35rem;
        padding: 0 1.1rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
    }
    .faq-modal-btn--cancel {
        background: #f1f5f9;
        color: #475569;
        border-color: #e2e8f0;
    }
    .dark .faq-modal-btn--cancel {
        background: #1e293b;
        color: #94a3b8;
        border-color: #334155;
    }
    .faq-modal-btn--cancel:hover { background: #e2e8f0; }
    .dark .faq-modal-btn--cancel:hover { background: #334155; }
    .faq-modal-btn--save {
        background: #4f46e5;
        color: #fff;
        border-color: #4f46e5;
        box-shadow: 0 2px 8px rgb(79 70 229 / 0.3);
    }
    .faq-modal-btn--save:hover {
        background: #4338ca;
        border-color: #4338ca;
        box-shadow: 0 4px 12px rgb(79 70 229 / 0.4);
    }
</style>
