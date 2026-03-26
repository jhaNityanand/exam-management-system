@props([
    'class' => '',
    'bodyClass' => '',
])

<section {{ $attributes->merge(['class' => "panel-card {$class}"]) }}>
    <div class="panel-card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
</section>
