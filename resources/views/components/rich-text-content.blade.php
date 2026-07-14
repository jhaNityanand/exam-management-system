{{-- Read-only rich HTML display (exam/question show pages). --}}
@props([
    'content' => '',
    'class' => '',
])

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}">
    @endpush
@endonce

<div {{ $attributes->merge(['class' => 'ems-rich-content prose prose-slate dark:prose-invert max-w-none '.$class]) }}>
    {!! $content !!}
</div>
