{{-- Source chooser (Google vs Custom) --}}
<div id="ads-source-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-source-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="source"></div>
    <div class="ems-dialog__panel ems-dialog__panel--md" role="document">
        <header class="ems-dialog__header">
            <div class="min-w-0">
                <h3 id="ads-source-title" class="ems-dialog__title">Add advertisement</h3>
                <p class="ems-dialog__subtitle" data-ads-source-subtitle>Choose what to place in this slot.</p>
            </div>
            <button type="button" class="ems-dialog__close" data-ads-modal-close="source" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ems-dialog__body">
            <div class="ads-source-grid">
                <button type="button" class="ads-source-card" data-ads-choose-source="google">
                    <span class="ads-source-card__icon" aria-hidden="true">G</span>
                    <span class="ads-source-card__title">Google Ad</span>
                    <span class="ads-source-card__text">Select an existing Google AdSense / unit configuration.</span>
                </button>
                <button type="button" class="ads-source-card" data-ads-choose-source="custom">
                    <span class="ads-source-card__icon" aria-hidden="true">C</span>
                    <span class="ads-source-card__title">Custom Advertisement</span>
                    <span class="ads-source-card__text">Select a banner, iframe, or HTML ad from Advertisements.</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Pick existing ad --}}
<div id="ads-pick-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-pick-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="pick"></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <header class="ems-dialog__header">
            <div class="min-w-0">
                <h3 id="ads-pick-title" class="ems-dialog__title">Select advertisement</h3>
                <p class="ems-dialog__subtitle" data-ads-pick-subtitle>Choose one configuration to place.</p>
            </div>
            <button type="button" class="ems-dialog__close" data-ads-modal-close="pick" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ems-dialog__body space-y-4">
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                <input type="search" class="panel-input text-sm w-full sm:max-w-xs" placeholder="Search…" data-ads-pick-search>
                <button type="button" class="panel-button-secondary text-sm whitespace-nowrap" data-ads-pick-create>
                    Create new
                </button>
            </div>
            <div class="ads-pick-list" data-ads-pick-list></div>
            <p class="hidden text-sm text-slate-500 dark:text-slate-400 text-center py-6" data-ads-pick-empty>
                No matching advertisements found.
            </p>
        </div>
    </div>
</div>

{{-- Placement actions --}}
<div id="ads-placement-actions-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-placement-actions-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="actions"></div>
    <div class="ems-dialog__panel ems-dialog__panel--md" role="document">
        <header class="ems-dialog__header">
            <div class="min-w-0">
                <h3 id="ads-placement-actions-title" class="ems-dialog__title">Manage placement</h3>
                <p class="ems-dialog__subtitle" data-ads-actions-subtitle></p>
            </div>
            <button type="button" class="ems-dialog__close" data-ads-modal-close="actions" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ems-dialog__body">
            <div class="ads-action-list">
                <button type="button" class="ads-action-btn" data-ads-action="replace">Replace advertisement</button>
                <button type="button" class="ads-action-btn" data-ads-action="toggle">Enable / Disable</button>
                <button type="button" class="ads-action-btn ads-action-btn--danger" data-ads-action="remove">Remove placement</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom advertisement create/edit --}}
<div id="ads-form-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-form-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="form"></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="ad-form" class="ems-dialog__form" novalidate>
            <header class="ems-dialog__header">
                <div class="min-w-0">
                    <h3 id="ads-form-title" class="ems-dialog__title">Create advertisement</h3>
                    <p class="ems-dialog__subtitle">Choose one advertisement type and fill only the relevant fields.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-ads-modal-close="form" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </header>

            <div class="ems-dialog__body space-y-5">
                <input type="hidden" name="id" id="ad_id" value="">

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Basics</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="ad_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" id="ad_name" name="name" class="panel-input w-full" maxlength="160" required placeholder="Sidebar promo">
                            <p class="qcat-field-error" data-error-for="name" hidden></p>
                        </div>
                        <div>
                            <label for="ad_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Advertisement title</label>
                            <input type="text" id="ad_title" name="title" class="panel-input w-full" maxlength="160" placeholder="Optional display title">
                            <p class="qcat-field-error" data-error-for="title" hidden></p>
                        </div>
                        <div>
                            <label for="ad_status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                            <select id="ad_status" name="status" class="panel-input w-full">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <p class="qcat-field-error" data-error-for="status" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="ems-dialog__section">
                    <h4 class="ems-dialog__section-title">Advertisement type</h4>
                    <div class="ads-type-switch" role="radiogroup" aria-label="Advertisement type">
                        @foreach($types as $key => $label)
                            <label class="ads-type-option">
                                <input type="radio" name="type" value="{{ $key }}" @checked($key === 'banner') data-ads-type-radio>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="qcat-field-error" data-error-for="type" hidden></p>
                </section>

                {{-- Banner fields --}}
                <section class="ems-dialog__section" data-ads-type-panel="banner">
                    <h4 class="ems-dialog__section-title">Banner image</h4>
                    <div class="space-y-4">
                        <div>
                            <label for="ad_banner_size" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Banner size <span class="text-red-500">*</span></label>
                            <select id="ad_banner_size" name="banner_size" class="panel-input w-full" data-ads-banner-size>
                                <option value="">Select size…</option>
                                @foreach($bannerSizes as $key => $size)
                                    <option
                                        value="{{ $key }}"
                                        data-note="{{ $size['note'] }}"
                                        data-width="{{ $size['width'] }}"
                                        data-height="{{ $size['height'] }}"
                                        data-label="{{ $size['label'] }}"
                                    >
                                        {{ $size['label'] }} — {{ $size['width'] }} × {{ $size['height'] }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" data-ads-banner-note>
                                Select a size to see placement recommendations.
                            </p>
                            <p class="qcat-field-error" data-error-for="banner_size" hidden></p>
                        </div>

                        @include('backend.partials.gallery-picker', [
                            'name' => 'image_id',
                            'label' => 'Banner image',
                            'value' => null,
                            'inputId' => 'ad_image_id',
                            'previewId' => 'ad_image_preview',
                            'recommendKey' => 'ad_medium_rectangle',
                        ])
                        <p class="qcat-field-error" data-error-for="image_id" hidden></p>
                        <p class="mt-1.5 text-xs text-teal-700 dark:text-teal-300" data-ads-image-size-hint>
                            {{ \App\Support\ImageSizeGuide::hint('ad_medium_rectangle') }} Choose a banner size above to update this recommendation.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="ad_target_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Target URL</label>
                                <input type="text" id="ad_target_url" name="target_url" class="panel-input w-full" placeholder="/exams or https://…">
                                <p class="qcat-field-error" data-error-for="target_url" hidden></p>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" id="ad_open_in_new_tab" name="open_in_new_tab" value="1" class="rounded border-slate-300 dark:border-slate-600" checked>
                                    Open in new tab
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Iframe fields --}}
                <section class="ems-dialog__section hidden" data-ads-type-panel="iframe">
                    <h4 class="ems-dialog__section-title">Iframe advertisement</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                        Use iframes for partner widgets or hosted creatives you do not control directly. Prefer HTTPS URLs and responsive sizing for mobile.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label for="ad_iframe_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Iframe URL <span class="text-red-500">*</span></label>
                            <input type="url" id="ad_iframe_url" name="iframe_url" class="panel-input w-full" placeholder="https://…">
                            <p class="qcat-field-error" data-error-for="iframe_url" hidden></p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="ad_width" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Width <span class="text-red-500">*</span></label>
                                <input type="number" id="ad_width" name="width" class="panel-input w-full" min="1" max="5000" placeholder="300">
                                <p class="qcat-field-error" data-error-for="width" hidden></p>
                            </div>
                            <div>
                                <label for="ad_height" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Height <span class="text-red-500">*</span></label>
                                <input type="number" id="ad_height" name="height" class="panel-input w-full" min="1" max="5000" placeholder="250">
                                <p class="qcat-field-error" data-error-for="height" hidden></p>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" id="ad_is_responsive" name="is_responsive" value="1" class="rounded border-slate-300 dark:border-slate-600" checked>
                                    Responsive
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- HTML fields --}}
                <section class="ems-dialog__section hidden" data-ads-type-panel="html">
                    <h4 class="ems-dialog__section-title">HTML advertisement</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                        Use for lightweight custom creatives. Keep scripts minimal and avoid conflicting global CSS selectors.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label for="ad_html_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">HTML code <span class="text-red-500">*</span></label>
                            <textarea id="ad_html_code" name="html_code" rows="6" class="panel-input w-full font-mono text-xs"></textarea>
                            <p class="qcat-field-error" data-error-for="html_code" hidden></p>
                        </div>
                        <div>
                            <label for="ad_css_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CSS</label>
                            <textarea id="ad_css_code" name="css_code" rows="4" class="panel-input w-full font-mono text-xs"></textarea>
                            <p class="qcat-field-error" data-error-for="css_code" hidden></p>
                        </div>
                        <div>
                            <label for="ad_js_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">JavaScript</label>
                            <textarea id="ad_js_code" name="js_code" rows="4" class="panel-input w-full font-mono text-xs" placeholder="Optional"></textarea>
                            <p class="qcat-field-error" data-error-for="js_code" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="ems-dialog__section">
                    <label for="ad_notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Internal notes</label>
                    <textarea id="ad_notes" name="notes" rows="2" class="panel-input w-full" placeholder="Optional notes for administrators"></textarea>
                    <p class="qcat-field-error" data-error-for="notes" hidden></p>
                </section>
            </div>

            <footer class="ems-dialog__footer">
                <button type="button" class="panel-button-secondary text-sm" data-ads-modal-close="form">Cancel</button>
                <button type="submit" class="panel-button-primary text-sm" data-ads-form-submit>Save advertisement</button>
            </footer>
        </form>
    </div>
</div>

{{-- Google Ad create/edit --}}
<div id="ads-google-form-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-google-form-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="google"></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <form id="google-ad-form" class="ems-dialog__form" novalidate>
            <header class="ems-dialog__header">
                <div class="min-w-0">
                    <h3 id="ads-google-form-title" class="ems-dialog__title">Google Ad configuration</h3>
                    <p class="ems-dialog__subtitle">Paste your AdSense / Google ad unit snippet and optional metadata.</p>
                </div>
                <button type="button" class="ems-dialog__close" data-ads-modal-close="google" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </header>
            <div class="ems-dialog__body space-y-4">
                <input type="hidden" name="id" id="google_ad_id" value="">
                <div>
                    <label for="google_ad_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="google_ad_name" name="name" class="panel-input w-full" required maxlength="160">
                    <p class="qcat-field-error" data-error-for="name" hidden></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="google_ad_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ad client</label>
                        <input type="text" id="google_ad_client" name="ad_client" class="panel-input w-full" placeholder="ca-pub-…">
                        <p class="qcat-field-error" data-error-for="ad_client" hidden></p>
                    </div>
                    <div>
                        <label for="google_ad_slot" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ad slot</label>
                        <input type="text" id="google_ad_slot" name="ad_slot" class="panel-input w-full">
                        <p class="qcat-field-error" data-error-for="ad_slot" hidden></p>
                    </div>
                    <div>
                        <label for="google_ad_format" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Format</label>
                        <input type="text" id="google_ad_format" name="ad_format" class="panel-input w-full" placeholder="auto / fluid / rectangle">
                        <p class="qcat-field-error" data-error-for="ad_format" hidden></p>
                    </div>
                </div>
                <div>
                    <label for="google_ad_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ad code <span class="text-red-500">*</span></label>
                    <textarea id="google_ad_code" name="code" rows="8" class="panel-input w-full font-mono text-xs" required></textarea>
                    <p class="qcat-field-error" data-error-for="code" hidden></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="google_ad_status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select id="google_ad_status" name="status" class="panel-input w-full">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="google_ad_notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                        <input type="text" id="google_ad_notes" name="notes" class="panel-input w-full">
                    </div>
                </div>
            </div>
            <footer class="ems-dialog__footer">
                <button type="button" class="panel-button-secondary text-sm" data-ads-modal-close="google">Cancel</button>
                <button type="submit" class="panel-button-primary text-sm">Save Google Ad</button>
            </footer>
        </form>
    </div>
</div>

@include('backend.partials.image-editor-modal')
