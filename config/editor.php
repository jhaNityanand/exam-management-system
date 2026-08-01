<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rich Text Editor — single shared TinyMCE surface
    |--------------------------------------------------------------------------
    |
    | Shared Blade contract: <x-rich-text-editor>.
    | Runtime source of truth: public/js/components/editor.js
    | (EmsRichTextEditor). Every module — questions (body, options,
    | explanation), exams, blogs, news — mounts the same header toolbar.
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

    'image_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
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

    // Shared toolbar (actions wrap onto extra rows — nothing hidden).
    // Kept in sync with SHARED_TOOLBAR in public/js/components/editor.js.
    'toolbar_presets' => [
        'header' => 'undo redo | bold italic underline strikethrough | fontfamily fontsize emslinespace | blocks | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | blockquote codesample hr | link emsimage table emstabledesign emsshapes emsmedia attachment | removeformat emscodeview emsfullscreen',
        'full' => 'undo redo | bold italic underline strikethrough | fontfamily fontsize emslinespace | blocks | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | blockquote codesample hr | link emsimage table emstabledesign emsshapes emsmedia attachment | removeformat emscodeview emsfullscreen',
        'standard' => 'undo redo | bold italic underline strikethrough | fontfamily fontsize emslinespace | blocks | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | blockquote codesample hr | link emsimage table emstabledesign emsshapes emsmedia attachment | removeformat emscodeview emsfullscreen',
        'compact' => 'undo redo | bold italic underline strikethrough | fontfamily fontsize emslinespace | blocks | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | blockquote codesample hr | link emsimage table emstabledesign emsshapes emsmedia attachment | removeformat emscodeview emsfullscreen',
    ],

    'plugins' => [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'anchor', 'searchreplace', 'visualblocks', 'code',
        'insertdatetime', 'media', 'table',
        'codesample', 'nonbreaking', 'directionality', 'noneditable',
    ],
];
