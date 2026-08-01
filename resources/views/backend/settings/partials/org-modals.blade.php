{{-- Hero modal --}}
<div id="hero-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hero-modal-title">
    <div class="ems-dialog__backdrop" data-hero-modal-close></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="hero-form" class="ems-dialog__form">
            <header class="ems-dialog__header">
                <div class="min-w-0">
                    <h3 id="hero-modal-title" class="ems-dialog__title">Edit hero banner</h3>
                    <p class="ems-dialog__subtitle">Update slide content, CTAs, schedule, and images.</p>
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

{{-- Member modal --}}
<div id="member-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="member-modal-title">
    <div class="ems-dialog__backdrop" data-member-modal-close></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="member-form" class="ems-dialog__form">
            <header class="ems-dialog__header faq-modal-header">
                <div class="faq-modal-header__icon" aria-hidden="true">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="member-modal-title" class="ems-dialog__title">Add member</h3>
                    <p class="ems-dialog__subtitle">New members receive the <strong>org_admin</strong> role automatically.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-member-modal-close aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <div class="ems-dialog__body faq-modal-body">
                <input type="hidden" id="member_id" name="id" value="">

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Account</h4>
                    <div class="faq-field-group">
                        <div class="faq-field">
                            <label for="member_name" class="faq-field__label">Name <span class="faq-field__req">*</span></label>
                            <input type="text" id="member_name" name="name" required maxlength="120" class="faq-field__input" placeholder="Full name" autocomplete="name">
                            <p class="qcat-field-error" data-error-for="name" hidden></p>
                        </div>
                        <div class="faq-field">
                            <label for="member_email" class="faq-field__label">Email <span class="faq-field__req">*</span></label>
                            <input type="email" id="member_email" name="email" required maxlength="190" class="faq-field__input" placeholder="member@example.com" autocomplete="email">
                            <p class="qcat-field-error" data-error-for="email" hidden></p>
                        </div>
                        <div class="faq-field">
                            <label for="member_password" class="faq-field__label">
                                Password <span class="faq-field__req" id="member_password_req">*</span>
                            </label>
                            <input type="password" id="member_password" name="password" maxlength="120" class="faq-field__input" placeholder="••••••••" autocomplete="new-password">
                            <p class="faq-featured-toggle__hint" id="member_password_hint">Required for brand-new accounts. Optional when inviting an existing user (their current password is kept). Leave blank when editing to keep the current password.</p>
                            <p class="qcat-field-error" data-error-for="password" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Membership</h4>
                    <div class="faq-meta-grid">
                        <div class="faq-field">
                            <label for="member_role_display" class="faq-field__label">Role</label>
                            <input type="text" id="member_role_display" class="faq-field__input" value="Organization Admin (org_admin)" disabled>
                        </div>
                        <div class="faq-field">
                            <label for="member_status" class="faq-field__label">Status <span class="faq-field__req">*</span></label>
                            <select id="member_status" name="status" required class="faq-field__select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <p class="qcat-field-error" data-error-for="status" hidden></p>
                        </div>
                    </div>
                </section>

                <div class="faq-modal-tip" role="note">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;color:#6366f1;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>Members listed here can access the admin panel for this organization. Removing a member only detaches their membership.</p>
                </div>
            </div>

            <footer class="ems-dialog__footer">
                <button type="button" class="faq-modal-btn faq-modal-btn--cancel" data-member-modal-close>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancel
                </button>
                <button type="submit" class="faq-modal-btn faq-modal-btn--save" id="member-save-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save member
                </button>
            </footer>
        </form>
    </div>
</div>
