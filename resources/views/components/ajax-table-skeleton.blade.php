@props([
    'rows' => 10,
    'columns' => 5,
])

@for ($i = 0; $i < $rows; $i++)
    <tr class="ajax-table-skeleton-row" aria-hidden="true">
        @for ($c = 0; $c < $columns; $c++)
            @php
                $width = $c === $columns - 1 ? '40%' : (55 + (($i + $c) % 4) * 10) . '%';
                $align = $c === $columns - 1 ? 'text-right' : '';
            @endphp
            <td class="px-4 py-3 sm:px-6 sm:py-4 {{ $align }}">
                <div
                    class="ajax-skeleton-bar"
                    style="width: {{ $width }};{{ $c === $columns - 1 ? ' margin-left: auto;' : '' }}"
                ></div>
            </td>
        @endfor
    </tr>
@endfor
