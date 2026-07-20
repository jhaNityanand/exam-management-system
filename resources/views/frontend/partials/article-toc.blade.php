@php
    $html = (string) ($content ?? '');
    $toc = [];
    if ($html !== '' && preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $i => $match) {
            $text = trim(html_entity_decode(strip_tags($match[2])));
            if ($text === '') {
                continue;
            }
            $id = 'section-'.($i + 1).'-'.\Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($text, 40, ''));
            $toc[] = ['level' => (int) $match[1], 'text' => $text, 'id' => $id];
            $html = preg_replace('/'.preg_quote($match[0], '/').'/', '<h'.$match[1].' id="'.$id.'">'.$match[2].'</h'.$match[1].'>', $html, 1);
        }
    }
@endphp

@if(count($toc) >= 3)
    <nav class="et-toc" aria-label="Table of contents">
        <strong>On this page</strong>
        <ol>
            @foreach($toc as $item)
                <li class="et-toc__item et-toc__item--h{{ $item['level'] }}">
                    <a href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif

@php($processedContent = $html)
