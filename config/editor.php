<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rich Text Editor (TinyMCE)
    |--------------------------------------------------------------------------
    */
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

    'toolbar_presets' => [
        'full' => 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | blockquote codesample hr | link image media attachment table | emoticons charmap removeformat | searchreplace code preview fullscreen',
        'standard' => 'undo redo | blocks fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | searchreplace code preview fullscreen',
        'compact' => 'undo redo | bold italic underline | bullist numlist | link image | removeformat',
    ],

    'plugins' => [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
        'emoticons', 'codesample', 'pagebreak', 'nonbreaking', 'directionality',
    ],
];
