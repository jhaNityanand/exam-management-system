@props([
    'key',
    'label',
    'class' => '',
    'buttonClass' => 'list-sort-btn',
])

<th scope="col" {{ $attributes->merge(['class' => 'list-table__heading '.$class]) }}>
    <button type="button"
        class="{{ $buttonClass }}"
        data-sort-key="{{ $key }}"
        aria-label="Sort by {{ $label }}"
        aria-sort="none">
        <span>{{ $label }}</span>
        <span class="list-sort-icon" aria-hidden="true">
            <svg class="list-sort-icon__idle" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2.75 11.5 7h-7L8 2.75Zm0 10.5L4.5 9h7L8 13.25Z"/></svg>
            <svg class="list-sort-icon__asc" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3l3.5 4h-7L8 3Z"/></svg>
            <svg class="list-sort-icon__desc" viewBox="0 0 16 16" fill="currentColor"><path d="m8 13-3.5-4h7L8 13Z"/></svg>
        </span>
    </button>
</th>
