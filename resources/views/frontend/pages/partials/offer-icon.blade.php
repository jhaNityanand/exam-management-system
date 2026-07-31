@php
    $paths = [
        'exam' => 'M4 5h16v14H4zM8 9h8M8 13h5',
        'questions' => 'M12 3a9 9 0 100 18 9 9 0 000-18zm0 13h.01M9.5 9.5a2.5 2.5 0 114.2 1.8c-.7.7-1.7 1.2-1.7 2.2',
        'blogs' => 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5',
        'news' => 'M4 5h12v14H4zM16 8h4v11h-4M7 9h6M7 13h6',
        'resources' => 'M4 7h6l2-2h8v14H4V7z',
        'materials' => 'M7 4h10v16H7zM10 8h4M10 12h4M10 16h3',
        'career' => 'M4 10h16v9H4zM8 10V8a4 4 0 018 0v2',
        'share' => 'M12 5v10M8 9l4-4 4 4M5 19h14',
    ];
    $d = $paths[$icon ?? 'exam'] ?? $paths['exam'];
@endphp
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="{{ $d }}" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
