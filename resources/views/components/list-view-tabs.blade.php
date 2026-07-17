@props([
    'activeLabel' => 'Active',
    'binLabel' => 'Bin',
])

<div
    {{ $attributes->class(['list-view-tabs'])->merge([
        'role' => 'tablist',
        'aria-label' => 'Record visibility',
    ]) }}
>
    <button type="button" role="tab" aria-selected="true" data-trash="active" class="is-active">
        {{ $activeLabel }}
    </button>
    <button type="button" role="tab" aria-selected="false" data-trash="bin">
        {{ $binLabel }}
    </button>
</div>
