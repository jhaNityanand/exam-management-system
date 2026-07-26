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
    <div class="ems-dialog__panel ems-dialog__panel--md" role="document">
        <form id="faq-form" class="ems-dialog__form">
            <header class="ems-dialog__header">
                <div class="min-w-0">
                    <h3 id="faq-modal-title" class="ems-dialog__title">Add FAQ</h3>
                    <p class="ems-dialog__subtitle">Shown on the public homepage when status is active.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-faq-modal-close aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <div class="ems-dialog__body space-y-4">
                <input type="hidden" id="faq_id" name="id" value="">
                <div>
                    <label for="faq_question" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Question <span class="text-red-500">*</span></label>
                    <input type="text" id="faq_question" name="question" required maxlength="500" class="panel-input mt-1 block w-full" placeholder="How do I begin practicing?">
                    <p class="qcat-field-error" data-error-for="question" hidden></p>
                </div>
                <div>
                    <label for="faq_answer" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Answer <span class="text-red-500">*</span></label>
                    <textarea id="faq_answer" name="answer" required rows="5" maxlength="10000" class="panel-input mt-1 block w-full" placeholder="Write a clear answer…"></textarea>
                    <p class="qcat-field-error" data-error-for="answer" hidden></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label for="faq_category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select id="faq_category_id" name="faq_category_id" class="panel-input mt-1 block w-full">
                            <option value="">Uncategorized</option>
                            @foreach($faqCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="faq_status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="faq_status" name="status" required class="panel-input mt-1 block w-full">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="faq_sort_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sort order</label>
                        <input type="number" id="faq_sort_order" name="sort_order" min="0" max="9999" value="0" class="panel-input mt-1 block w-full">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" id="faq_is_featured" name="is_featured" value="1" class="rounded border-slate-300 text-indigo-600">
                            Featured on homepage
                        </label>
                    </div>
                </div>
            </div>

            <footer class="ems-dialog__footer">
                <button type="button" class="panel-button-secondary" data-faq-modal-close>Cancel</button>
                <button type="submit" class="panel-button-primary" id="faq-save-btn">Save FAQ</button>
            </footer>
        </form>
    </div>
</div>
