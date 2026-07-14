@php
    $blog = $blog ?? null;
    $seoItem = $blog;
    $selectedTags = old('tags', $blog ? $blog->tags->pluck('name')->all() : []);
    $attachmentIds = old('attachment_ids', $blog ? $blog->galleryAttachments->pluck('id')->all() : []);
    $publishedAtValue = old('published_at', $blog?->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '');
@endphp

<div class="px-4 py-5 sm:p-6 space-y-8">
    {{-- Title & Slug --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" id="title" name="title" value="{{ old('title', $blog?->title ?? '') }}" class="panel-input mt-1 block w-full" placeholder="Blog post title" required>
            @error('title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $blog?->slug ?? '') }}" class="panel-input mt-1 block w-full" placeholder="auto-generated-from-title">
            <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Leave blank to auto-generate from title.</p>
            @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Category, Status, Published --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <label for="blog_category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category</label>
            <select id="blog_category_id" name="blog_category_id" class="mt-1 block w-full">
                <option value="">None</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}"
                        data-level="{{ $cat->depth }}"
                        data-category-name="{{ $cat->name }}"
                        {{ old('blog_category_id', $blog?->blog_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('blog_category_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
            <select id="status" name="status" class="panel-input mt-1 block w-full">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" {{ old('status', $blog?->status ?? 'published') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="published_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Published At</label>
            <input type="datetime-local" id="published_at" name="published_at" value="{{ $publishedAtValue }}" class="panel-input mt-1 block w-full">
            @error('published_at')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Author --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="author_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Author</label>
            <select id="author_id" name="author_id" class="panel-input mt-1 block w-full">
                <option value="">Select author…</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}"
                        data-name="{{ $author->name }}"
                        {{ old('author_id', $blog?->author_id ?? auth()->id()) == $author->id ? 'selected' : '' }}>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
            @error('author_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="author_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Author Display Name</label>
            <input type="text" id="author_name" name="author_name" value="{{ old('author_name', $blog?->author_name ?? auth()->user()?->name) }}" class="panel-input mt-1 block w-full">
            @error('author_name')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Excerpt --}}
    <div>
        <label for="excerpt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Excerpt</label>
        <textarea id="excerpt" name="excerpt" rows="3" class="panel-input mt-1 block w-full" placeholder="Short summary for listings and SEO…">{{ old('excerpt', $blog?->excerpt ?? '') }}</textarea>
        @error('excerpt')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Banner --}}
    <div class="max-w-md">
        @include('backend.partials.gallery-picker', [
            'name' => 'banner_image_id',
            'label' => 'Banner Image',
            'multiple' => false,
            'value' => old('banner_image_id', $blog?->banner_image_id ?? null),
            'kind' => 'image',
        ])
        @error('banner_image_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Content --}}
    <div class="w-full relative z-0">
        <x-rich-text-editor
            label="Content"
            input-id="content"
            name="content"
            :value="old('content', $blog?->content ?? '')"
            placeholder="Write your blog post…"
            :height="360"
            preset="full"
        />
        @error('content')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Tags --}}
    <div>
        <label for="tags" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tags</label>
        <select id="tags" name="tags[]" multiple placeholder="Add tags…">
            @foreach ($tags as $tag)
                <option value="{{ $tag->name }}" {{ in_array($tag->name, $selectedTags, true) ? 'selected' : '' }}>{{ $tag->name }}</option>
            @endforeach
            @foreach ($selectedTags as $tagName)
                @if (!$tags->contains('name', $tagName))
                    <option value="{{ $tagName }}" selected>{{ $tagName }}</option>
                @endif
            @endforeach
        </select>
        @error('tags')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Attachments --}}
    <div>
        @include('backend.partials.gallery-picker', [
            'name' => 'attachment_ids',
            'label' => 'Attachments',
            'multiple' => true,
            'value' => $attachmentIds,
            'kind' => 'image',
        ])
        @error('attachment_ids')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>
</div>

{{-- SEO & Metadata --}}
<div id="metadata-section" class="category-builder__metadata">
    <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false" tabindex="0">
        <div class="qcat-meta-header-left">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="qcat-meta-title">SEO &amp; Metadata</span>
            <span class="qcat-meta-badge">Optional</span>
        </div>
        <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>

    <div id="meta-accordion-body" class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800 px-4 sm:px-6 pb-6">
        <p class="qcat-meta-hint mb-4">Optimize how this post appears in search engines and social shares.</p>

        <div class="qcat-seo-row qcat-seo-row--toggles">
            <div class="qcat-seo-col col-lg-4">
                <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                    <input type="hidden" name="ai_generated" value="0">
                    <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                        class="qcat-ai-checkbox" @checked(old('ai_generated', $seoItem?->ai_generated ?? false))>
                    <span class="qcat-ai-toggle-wrap"><span class="qcat-ai-thumb"></span></span>
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
                    <span class="qcat-ai-toggle-wrap"><span class="qcat-ai-thumb"></span></span>
                    <span class="qcat-ai-text">
                        <span class="qcat-ai-title">Improve with AI</span>
                        <span class="qcat-ai-hint">Queue for AI improvement</span>
                    </span>
                </label>
            </div>
            <div class="qcat-seo-col col-lg-4"></div>
        </div>

        <div id="manual-seo-fields-wrapper" class="space-y-4">
            <div class="qcat-seo-row qcat-seo-row--three-cols">
                <div class="qcat-meta-field col-lg-4">
                    <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                    <input type="text" id="meta-title" name="meta_title" value="{{ old('meta_title', $seoItem?->seo_title ?? '') }}" placeholder="SEO title" class="panel-input qcat-meta-input">
                    <span class="qcat-meta-count" data-max="255">0 / 255</span>
                    @error('seo_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>
                <div class="qcat-meta-field col-lg-4">
                    <label class="qcat-meta-label" for="blog-seo-slug">URL Slug</label>
                    <input type="text" id="blog-seo-slug" value="{{ old('slug', $blog?->slug ?? '') }}" class="panel-input qcat-meta-input" readonly tabindex="-1" aria-readonly="true">
                    <p class="mt-1 text-xs text-slate-400">Synced from main slug field.</p>
                </div>
                <div class="qcat-meta-field col-lg-4">
                    <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                    <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" class="panel-input qcat-meta-input">
                </div>
            </div>

            <div class="qcat-seo-row qcat-seo-row--two-cols">
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                    <textarea id="meta-desc" name="meta_description" rows="2" class="panel-input qcat-meta-textarea">{{ old('meta_description', $seoItem?->seo_description ?? '') }}</textarea>
                    <span class="qcat-meta-count" data-max="500">0 / 500</span>
                    @error('seo_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                    <textarea id="meta-og-desc" name="og_description" rows="2" class="panel-input qcat-meta-textarea">{{ old('og_description', $seoItem?->og_description ?? '') }}</textarea>
                </div>
            </div>

            <div class="qcat-seo-row qcat-seo-row--two-cols">
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-keywords">Meta Keywords</label>
                    <input type="text" id="meta-keywords" name="meta_keywords" value="{{ old('meta_keywords', $seoItem?->seo_keywords ?? '') }}" class="panel-input qcat-meta-input">
                </div>
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                    <input type="url" id="meta-canonical" name="canonical_url" value="{{ old('canonical_url', $seoItem?->canonical_url ?? '') }}" class="panel-input qcat-meta-input">
                </div>
            </div>

            <div class="qcat-seo-row qcat-seo-row--two-cols">
                <div class="qcat-meta-field col-lg-6">
                    <label class="qcat-meta-label" for="meta-robots">Robots</label>
                    <select id="meta-robots" name="robots" class="panel-input qcat-meta-input">
                        @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                            <option value="{{ $robots }}" {{ old('robots', $seoItem?->robots ?? 'index,follow') === $robots ? 'selected' : '' }}>{{ $robots }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="qcat-meta-field col-lg-6">
                    @include('backend.partials.gallery-picker', [
                        'name' => 'og_image_id',
                        'label' => 'OG Image',
                        'multiple' => false,
                        'value' => old('og_image_id', $seoItem?->og_image_id ?? null),
                        'kind' => 'image',
                    ])
                </div>
            </div>

            <div class="qcat-meta-field">
                <label class="qcat-meta-label" for="meta-schema">Schema Markup (JSON-LD)</label>
                <textarea id="meta-schema" name="schema_markup" rows="4" class="panel-input qcat-meta-textarea font-mono text-xs">{{ old('schema_markup', $seoItem?->schema_markup ?? '') }}</textarea>
            </div>

            {{-- SEO Preview Card --}}
            <div class="blog-seo-preview" id="blog-seo-preview">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Search Preview</p>
                <div class="blog-seo-preview__card">
                    <div id="seo-preview-title" class="blog-seo-preview__title">Page title preview</div>
                    <div id="seo-preview-url" class="blog-seo-preview__url">{{ url('/') }}/blog/example-slug</div>
                    <div id="seo-preview-desc" class="blog-seo-preview__desc">Meta description preview will appear here.</div>
                </div>
            </div>
        </div>
    </div>
</div>
