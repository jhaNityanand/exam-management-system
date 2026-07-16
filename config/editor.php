<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rich Text Editor — simple header toolbar (Linear/Jira look & feel)
    |--------------------------------------------------------------------------
    |
    | Shared Blade contract: <x-rich-text-editor>. Default UI is "header":
    | one clean, always-visible action row on top, writing area below,
    | plus "/" slash commands and "@" mentions for power users. Use
    | mode="compact" for small inline editors (e.g. question options).
    | Uploads go through GalleryService via POST admin/editor/media.
    |
    */
    'ui_mode' => env('EDITOR_UI_MODE', 'header'),

    'cdn' => [
        'version' => env('TINYMCE_VERSION', '7.6.1'),
        'base_url' => env('TINYMCE_BASE_URL', 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1'),
    ],

    'disk' => env('EDITOR_MEDIA_DISK', 'public'),

    // Legacy key kept for compatibility; media is stored under gallery/.
    'directory' => 'gallery',

    // Keep defaults within common PHP post_max_size / upload_max_filesize limits.
    'max_image_kb' => (int) env('EDITOR_MAX_IMAGE_KB', 2048),      // 2 MB
    'max_video_kb' => (int) env('EDITOR_MAX_VIDEO_KB', 20480),     // 20 MB
    'max_file_kb' => (int) env('EDITOR_MAX_FILE_KB', 10240),       // 10 MB

    'image_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'video_mimes' => ['mp4', 'webm', 'ogg'],
    'file_mimes' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', '7z',
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'mp4', 'webm',
    ],

    /*
    | Orphan editor uploads with no referencing HTML after this many hours
    | may be pruned by the gallery:prune-orphans command.
    */
    'orphan_ttl_hours' => (int) env('EDITOR_ORPHAN_TTL_HOURS', 24),

    // Toolbar presets. "header" is a single-row toolbar; TinyMCE collapses
    // overflow items behind a "…" button (toolbar_mode: floating), so every
    // action stays available without wrapping into extra rows.
    'toolbar_presets' => [
        'header' => 'fullscreen | fontfamily fontsize | blocks | bold italic underline strikethrough superscript subscript | forecolor backcolor | align | bullist numlist checklist outdent indent | blockquote codesample | link emsimage table media attachment | removeformat',
        'full' => 'fullscreen | fontfamily fontsize | blocks | bold italic underline strikethrough superscript subscript | forecolor backcolor | align | bullist numlist checklist outdent indent | blockquote codesample | link emsimage table media attachment | removeformat',
        'standard' => 'fullscreen | blocks | bold italic underline | bullist numlist | link emsimage table | removeformat',
        'compact' => 'fullscreen | bold italic underline strikethrough | forecolor | bullist numlist | link emsimage | removeformat',
    ],

    'plugins' => [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount',
        'codesample', 'nonbreaking', 'directionality',
    ],
];
