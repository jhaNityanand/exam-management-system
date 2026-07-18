@php
    $blog = $blog ?? null;
    $seoItem = $blog;
    $selectedTags = old('tags', $blog ? $blog->tags->pluck('name')->all() : []);
    $attachmentIds = old('attachment_ids', $blog ? $blog->galleryAttachments->pluck('id')->all() : []);
    $bannerIds = old(
        'banner_ids',
        $blog
            ? (
                $blog->relationLoaded('banners') && $blog->banners->isNotEmpty()
                    ? $blog->banners->pluck('id')->all()
                    : array_values(array_filter([(int) $blog->banner_image_id]))
            )
            : []
    );
    $bannerItems = [];
    if ($blog?->relationLoaded('banners')) {
        foreach ($blog->banners as $gallery) {
            $bannerItems[] = [
                'id' => $gallery->id,
                'url' => $gallery->file_url,
                'name' => $gallery->original_name,
            ];
        }
    } elseif ($blog?->bannerImage) {
        $bannerItems[] = [
            'id' => $blog->bannerImage->id,
            'url' => $blog->bannerImage->file_url,
            'name' => $blog->bannerImage->original_name,
        ];
    }
    $publishedAtValue = old('published_at', $blog?->published_at);
    $publishedAtInitial = $blog?->published_at?->format('Y-m-d H:i');
@endphp

<div class="px-4 py-5 sm:p-6 space-y-8">
    {{-- Title --}}
    <div>
        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $blog?->title ?? '') }}" class="panel-input mt-1 block w-full" placeholder="Blog post title" required>
        @error('title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
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
            <select id="status" name="status" class="mt-1 block w-full">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" {{ old('status', $blog?->status ?? 'published') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-date-time-picker
                name="published_at"
                id="published_at"
                mode="datetime"
                label="Published At"
                :value="$publishedAtValue"
                help="Optional. Schedule for a future date and time, or leave empty to publish immediately."
                data-initial-value="{{ $publishedAtInitial }}"
                data-min-date="future"
            />
            @error('published_at')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
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

    {{-- Banners (multi) --}}
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
            label="Content"
            input-id="content"
            name="content"
            :value="old('content', $blog?->content ?? '')"
            placeholder="Write your blog post…"
            :height="360"
            preset="full"
            module="blog"
        />
        @error('content')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
    </div>

    {{-- Tags --}}
    <div class="blog-tags-field">
        <label for="tags" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tags</label>
        <select id="tags" name="tags[]" multiple placeholder="Type a tag and press Enter…" class="blog-tags-select">
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
    'slugValue' => old('slug', $blog?->slug ?? ''),
    'metaTitleError' => 'seo_title',
    'metaDescriptionError' => 'seo_description',
    'metaKeywordsError' => 'seo_keywords',
    'bodyClass' => 'px-4 sm:px-6 pb-6',
    'showPublishingExtras' => true,
    'previewBaseUrl' => url('/blogs'),
    'previewClassPrefix' => 'blog',
])

