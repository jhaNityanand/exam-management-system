@php
    $news = $news ?? null;
    $seoItem = $news;
    $visibilities = $visibilities ?? \App\Models\News::visibilities();
    $selectedTags = old('tags', $news ? $news->tags->pluck('name')->all() : []);
    $attachmentIds = old('attachment_ids', $news ? $news->galleryAttachments->pluck('id')->all() : []);
    $featuredImageId = old('featured_image_id', $news?->featured_image_id);
    $bannerIds = old(
        'banner_ids',
        $news
            ? (
                $news->relationLoaded('banners') && $news->banners->isNotEmpty()
                    ? $news->banners->pluck('id')->all()
                    : array_values(array_filter([(int) $news->banner_image_id]))
            )
            : []
    );
    $bannerItems = [];
    if ($news?->relationLoaded('banners')) {
        foreach ($news->banners as $gallery) {
            $bannerItems[] = [
                'id' => $gallery->id,
                'url' => $gallery->file_url,
                'name' => $gallery->original_name,
            ];
        }
    } elseif ($news?->bannerImage) {
        $bannerItems[] = [
            'id' => $news->bannerImage->id,
            'url' => $news->bannerImage->file_url,
            'name' => $news->bannerImage->original_name,
        ];
    }
    $publishedAtValue = old('published_at', $news?->published_at);
    $publishedAtInitial = $news?->published_at?->format('Y-m-d H:i');
    $expiresAtValue = old('expires_at', $news?->expires_at);
    $breakingUntilValue = old('breaking_until', $news?->breaking_until);
@endphp

<div class="px-4 py-5 sm:p-6 space-y-8">
    {{-- Title --}}
    <div>
        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $news?->title ?? '') }}" class="panel-input mt-1 block w-full" placeholder="News headline" required>
        @error('title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Category, Status, Visibility --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <label for="news_category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category</label>
            <select id="news_category_id" name="news_category_id" class="mt-1 block w-full">
                <option value="">None</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}"
                        data-level="{{ $cat->depth }}"
                        data-category-name="{{ $cat->name }}"
                        {{ old('news_category_id', $news?->news_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('news_category_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
            <select id="status" name="status" class="mt-1 block w-full">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" {{ old('status', $news?->status ?? 'published') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="visibility" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Visibility</label>
            <select id="visibility" name="visibility" class="mt-1 block w-full">
                @foreach ($visibilities as $key => $label)
                    <option value="{{ $key }}" {{ old('visibility', $news?->visibility ?? 'public') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('visibility')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Dates --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <x-date-time-picker
                name="published_at"
                id="published_at"
                mode="datetime"
                label="Publish Date"
                :value="$publishedAtValue"
                help="Optional. Schedule for a future date and time, or leave empty to publish immediately."
                data-initial-value="{{ $publishedAtInitial }}"
                data-min-date="future"
            />
            @error('published_at')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-date-time-picker
                name="expires_at"
                id="expires_at"
                mode="datetime"
                label="Expiry Date"
                :value="$expiresAtValue"
                help="Optional. News can be hidden after this time."
            />
            @error('expires_at')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-date-time-picker
                name="breaking_until"
                id="breaking_until"
                mode="datetime"
                label="Breaking News Until"
                :value="$breakingUntilValue"
                help="Optional duration window while the breaking flag is emphasized."
            />
            @error('breaking_until')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Flags & Sort --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <label class="qcat-ai-toggle-label news-flag-toggle" for="toggle-featured">
            <input type="hidden" name="is_featured" value="0">
            <input type="checkbox" name="is_featured" id="toggle-featured" value="1"
                class="qcat-ai-checkbox" @checked(old('is_featured', $news?->is_featured ?? false))>
            <span class="qcat-ai-toggle-wrap"><span class="qcat-ai-thumb"></span></span>
            <span class="qcat-ai-text">
                <span class="qcat-ai-title">Featured News</span>
                <span class="qcat-ai-hint">Highlight on featured placements</span>
            </span>
        </label>
        <label class="qcat-ai-toggle-label news-flag-toggle" for="toggle-breaking">
            <input type="hidden" name="is_breaking" value="0">
            <input type="checkbox" name="is_breaking" id="toggle-breaking" value="1"
                class="qcat-ai-checkbox" @checked(old('is_breaking', $news?->is_breaking ?? false))>
            <span class="qcat-ai-toggle-wrap"><span class="qcat-ai-thumb"></span></span>
            <span class="qcat-ai-text">
                <span class="qcat-ai-title">Breaking News</span>
                <span class="qcat-ai-hint">Mark as urgent / breaking</span>
            </span>
        </label>
        <label class="qcat-ai-toggle-label news-flag-toggle" for="toggle-trending">
            <input type="hidden" name="is_trending" value="0">
            <input type="checkbox" name="is_trending" id="toggle-trending" value="1"
                class="qcat-ai-checkbox" @checked(old('is_trending', $news?->is_trending ?? false))>
            <span class="qcat-ai-toggle-wrap"><span class="qcat-ai-thumb"></span></span>
            <span class="qcat-ai-text">
                <span class="qcat-ai-title">Trending News</span>
                <span class="qcat-ai-hint">Surface in trending lists</span>
            </span>
        </label>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" step="1"
                value="{{ old('sort_order', $news?->sort_order ?? 0) }}"
                class="panel-input mt-1 block w-full">
            @error('sort_order')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Author --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="author_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Author</label>
            <select id="author_id" name="author_id" class="mt-1 block w-full">
                <option value="">Select author…</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}"
                        data-name="{{ $author->name }}"
                        {{ old('author_id', $news?->author_id ?? auth()->id()) == $author->id ? 'selected' : '' }}>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
            @error('author_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="author_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Author Display Name</label>
            <input type="text" id="author_name" name="author_name" value="{{ old('author_name', $news?->author_name ?? auth()->user()?->name) }}" class="panel-input mt-1 block w-full">
            @error('author_name')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Descriptions --}}
    <div>
        <label for="short_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Short Description</label>
        <textarea id="short_description" name="short_description" rows="2" class="panel-input mt-1 block w-full" placeholder="One or two lines for cards and headlines…">{{ old('short_description', $news?->short_description ?? '') }}</textarea>
        @error('short_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>
    {{-- Featured image --}}
    <div>
        @include('backend.partials.gallery-picker', [
            'name' => 'featured_image_id',
            'label' => 'Featured Image',
            'multiple' => false,
            'value' => $featuredImageId,
            'previewUrl' => $news?->featuredImage?->file_url,
            'kind' => 'image',
        ])
        @error('featured_image_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Banners (multi) — reuses shared blog banner uploader + editor --}}
    <div>
        @include('backend.partials.blog-banner-uploader', [
            'name' => 'banner_ids',
            'label' => 'Banner Images',
            'value' => $bannerIds,
            'items' => $bannerItems,
        ])
        @error('banner_ids')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        @error('banner_ids.*')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Content --}}
    <div class="w-full relative z-0">
        <x-rich-text-editor
            label="Full Description"
            input-id="content"
            name="content"
            :value="old('content', $news?->content ?? '')"
            placeholder="Write the full news story…"
            :height="360"
            preset="full"
            module="news"
        />
        @error('content')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Tags --}}
    <div class="news-tags-field">
        <label for="tags" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tags</label>
        <select id="tags" name="tags[]" multiple placeholder="Type a tag and press Enter…" class="news-tags-select">
            @foreach ($tags as $tag)
                <option value="{{ $tag->name }}" {{ in_array($tag->name, $selectedTags, true) ? 'selected' : '' }}>{{ $tag->name }}</option>
            @endforeach
            @foreach ($selectedTags as $tagName)
                @if (!$tags->contains('name', $tagName))
                    <option value="{{ $tagName }}" selected>{{ $tagName }}</option>
                @endif
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Press Enter to add. Duplicates are ignored automatically.</p>
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

{{-- SEO & METADATA (shared partial — keep constant across modules) --}}
@include('backend.partials.seo-metadata-section', [
    'seoItem' => $seoItem,
    'showSlug' => true,
    'slugPlaceholder' => 'auto-generated-from-title',
    'slugValue' => old('slug', $news?->slug ?? ''),
    'metaTitleError' => 'seo_title',
    'metaDescriptionError' => 'seo_description',
    'metaKeywordsError' => 'seo_keywords',
    'bodyClass' => 'px-4 sm:px-6 pb-6',
    'showPublishingExtras' => true,
    'previewBaseUrl' => url('/news'),
    'previewClassPrefix' => 'news',
])

