{{--
    Shared SEO & Metadata accordion — keep identical across modules.

    Props:
      $seoItem (model|null)
      $showSlug (bool) default true
      $slugPlaceholder (string) default 'auto-generated-from-title'
      $slugValue (string|null) optional override for slug value
      $metaTitleValue / $metaDescriptionValue / $metaKeywordsValue (optional overrides)
      $metaTitleError / $metaDescriptionError / $metaKeywordsError (error bag keys)
      $bodyClass (string) extra classes on accordion body
      $showPublishingExtras (bool) robots + schema + search preview (blog/news)
      $previewBaseUrl (string) e.g. url('/blogs')
      $previewClassPrefix (string) 'blog' | 'news'
--}}
@php
    $seoItem = $seoItem ?? $item ?? null;
    $showSlug = $showSlug ?? true;
    $slugPlaceholder = $slugPlaceholder ?? 'auto-generated-from-title';
    $slugValue = $slugValue ?? old('slug', $seoItem?->slug ?? '');
    $metaTitleValue = $metaTitleValue ?? old('meta_title', $seoItem?->meta_title ?? $seoItem?->seo_title ?? '');
    $metaDescriptionValue = $metaDescriptionValue ?? old('meta_description', $seoItem?->meta_description ?? $seoItem?->seo_description ?? '');
    $metaKeywordsValue = $metaKeywordsValue ?? old('meta_keywords', $seoItem?->meta_keywords ?? $seoItem?->seo_keywords ?? '');
    $metaTitleError = $metaTitleError ?? 'meta_title';
    $metaDescriptionError = $metaDescriptionError ?? 'meta_description';
    $metaKeywordsError = $metaKeywordsError ?? 'meta_keywords';
    $bodyClass = $bodyClass ?? '';
    $showPublishingExtras = $showPublishingExtras ?? false;
    $previewBaseUrl = rtrim((string) ($previewBaseUrl ?? url('/')), '/');
    $previewClassPrefix = $previewClassPrefix ?? 'blog';
    $sectionClass = trim('category-builder__metadata '.($sectionClass ?? ''));
@endphp

<div id="metadata-section" class="{{ $sectionClass }}">
    <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false" tabindex="0">
        <div class="qcat-meta-header-left">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="qcat-meta-title">SEO &amp; Metadata</span>
            <span class="qcat-meta-badge">Optional</span>
        </div>
        <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>

    <div id="meta-accordion-body" class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800 {{ $bodyClass }}">
        <p class="qcat-meta-hint mb-4">Add SEO keywords, meta details, and titles to index this content properly.</p>

        <div class="qcat-seo-row qcat-seo-row--toggles">
            <div class="qcat-seo-col col-lg-4">
                <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                    <input type="hidden" name="ai_generated" value="0">
                    <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                        class="qcat-ai-checkbox" @checked(old('ai_generated', $seoItem?->ai_generated ?? false))>
                    <span class="qcat-ai-toggle-wrap">
                        <span class="qcat-ai-thumb"></span>
                    </span>
                    <span class="qcat-ai-text">
                        <span class="qcat-ai-title">Create with AI</span>
                        <span class="qcat-ai-hint">Let AI generate details automatically</span>
                    </span>
                </label>
            </div>

            <div class="qcat-seo-col col-lg-4" id="improve-with-ai-wrapper">
                <label class="qcat-ai-toggle-label" for="toggle-ai-improve">
                    <input type="hidden" name="ai_improve" value="0">
                    <input type="checkbox" name="ai_improve" id="toggle-ai-improve" value="1"
                        class="qcat-ai-checkbox" @checked(old('ai_improve', $seoItem?->ai_improve ?? false))>
                    <span class="qcat-ai-toggle-wrap">
                        <span class="qcat-ai-thumb"></span>
                    </span>
                    <span class="qcat-ai-text">
                        <span class="qcat-ai-title">Improve with AI</span>
                        <span class="qcat-ai-hint">Queue for AI improvement</span>
                    </span>
                </label>
            </div>

            <div class="qcat-seo-col col-lg-4"></div>
        </div>

        <div id="manual-seo-fields-wrapper" class="space-y-4">
            @if ($showSlug)
                <div class="qcat-seo-row qcat-seo-row--three-cols">
                    <div class="qcat-meta-field col-lg-4">
                        <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                        <input type="text" id="meta-title" name="meta_title" value="{{ $metaTitleValue }}" placeholder="e.g. Meta Title" class="panel-input qcat-meta-input">
                        <span class="qcat-meta-count" data-max="255">0 / 255</span>
                        @error($metaTitleError)<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>

                    <div class="qcat-meta-field col-lg-4">
                        <label class="qcat-meta-label" for="meta-slug">Slug</label>
                        <input type="text" id="meta-slug" name="slug" value="{{ $slugValue }}" placeholder="{{ $slugPlaceholder }}" class="panel-input qcat-meta-input" autocomplete="off">
                        <p class="ems-slug-status mt-1 text-xs text-slate-400" aria-live="polite"></p>
                        @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>

                    <div class="qcat-meta-field col-lg-4">
                        <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                        <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" placeholder="e.g. Open Graph Title" class="panel-input qcat-meta-input">
                        @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>
                </div>
            @else
                <div class="qcat-seo-row qcat-seo-row--two-cols">
                    <div class="qcat-meta-field col-lg-6">
                        <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                        <input type="text" id="meta-title" name="meta_title" value="{{ $metaTitleValue }}" placeholder="e.g. Meta Title" class="panel-input qcat-meta-input">
                        <span class="qcat-meta-count" data-max="255">0 / 255</span>
                        @error($metaTitleError)<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>

                    <div class="qcat-meta-field col-lg-6">
                        <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                        <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" placeholder="e.g. Open Graph Title" class="panel-input qcat-meta-input">
                        @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            <div class="qcat-seo-row qcat-seo-row--two-cols">
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                    <textarea id="meta-desc" name="meta_description" rows="2" placeholder="Brief description for search engines (up to 500 characters)" class="panel-input qcat-meta-textarea">{{ $metaDescriptionValue }}</textarea>
                    <span class="qcat-meta-count" data-max="500">0 / 500</span>
                    @error($metaDescriptionError)<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>

                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                    <textarea id="meta-og-desc" name="og_description" rows="2" placeholder="Open Graph Description" class="panel-input qcat-meta-textarea">{{ old('og_description', $seoItem?->og_description ?? '') }}</textarea>
                    @error('og_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="qcat-seo-row qcat-seo-row--two-cols">
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-keywords">Meta Keywords</label>
                    <input type="text" id="meta-keywords" name="meta_keywords" value="{{ $metaKeywordsValue }}" placeholder="keywords, comma, separated" class="panel-input qcat-meta-input">
                    @error($metaKeywordsError)<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>

                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                    <input type="url" id="meta-canonical" name="canonical_url" value="{{ old('canonical_url', $seoItem?->canonical_url ?? '') }}" placeholder="https://example.com/canonical" class="panel-input qcat-meta-input">
                    @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>
            </div>

            @include('backend.partials.seo-og-image', ['item' => $seoItem])

            @if ($showPublishingExtras)
                <div class="qcat-seo-row qcat-seo-row--two-cols">
                    <div class="qcat-meta-field col-lg-6">
                        <label class="qcat-meta-label" for="meta-robots">Robots</label>
                        <select id="meta-robots" name="robots" class="qcat-meta-input">
                            @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                                <option value="{{ $robots }}" {{ old('robots', $seoItem?->robots ?? 'index,follow') === $robots ? 'selected' : '' }}>{{ $robots }}</option>
                            @endforeach
                        </select>
                        @error('robots')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>
                    <div class="qcat-meta-field col-lg-6">
                        <label class="qcat-meta-label" for="meta-schema">Schema Markup (JSON-LD)</label>
                        <textarea id="meta-schema" name="schema_markup" rows="2" placeholder='{"@context":"https://schema.org","@type":"Article"}' class="panel-input qcat-meta-textarea font-mono text-xs">{{ old('schema_markup', $seoItem?->schema_markup ?? '') }}</textarea>
                        @error('schema_markup')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="ems-seo-preview {{ $previewClassPrefix }}-seo-preview" id="{{ $previewClassPrefix }}-seo-preview" data-seo-preview data-base-url="{{ $previewBaseUrl }}">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Search Preview</p>
                    <div class="ems-seo-preview__card {{ $previewClassPrefix }}-seo-preview__card">
                        <div id="seo-preview-title" class="ems-seo-preview__title {{ $previewClassPrefix }}-seo-preview__title">Page title preview</div>
                        <div id="seo-preview-url" class="ems-seo-preview__url {{ $previewClassPrefix }}-seo-preview__url">{{ $previewBaseUrl }}/example-slug</div>
                        <div id="seo-preview-desc" class="ems-seo-preview__desc {{ $previewClassPrefix }}-seo-preview__desc">Meta description preview will appear here.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
