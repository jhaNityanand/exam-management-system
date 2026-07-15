@php
    $stats = $stats ?? [];
    $items = [
        ['key' => 'exams', 'label' => 'Exams'],
        ['key' => 'questions', 'label' => 'Questions'],
        ['key' => 'blogs', 'label' => 'Blogs'],
        ['key' => 'news', 'label' => 'News'],
        ['key' => 'students', 'label' => 'Learners'],
        ['key' => 'categories', 'label' => 'Categories'],
    ];
@endphp
<div class="et-stats">
    @foreach($items as $item)
        @if(isset($stats[$item['key']]))
            <div class="et-stat">
                <span class="et-stat__value">{{ number_format((int) $stats[$item['key']]) }}</span>
                <span class="et-stat__label">{{ $item['label'] }}</span>
            </div>
        @endif
    @endforeach
</div>
