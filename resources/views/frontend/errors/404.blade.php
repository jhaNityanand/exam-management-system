@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Page not found'];
@endphp

@section('content')
    <div class="et-error-page">
        <div>
            <div class="et-error-page__code">404</div>
            <h1>Page not found</h1>
            <p>The page you are looking for may have moved or no longer exists.</p>
            <div style="display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap">
                <a href="{{ route('home') }}" class="et-btn et-btn--primary">Back home</a>
                @if(Route::has('frontend.search'))
                    <a href="{{ route('frontend.search') }}" class="et-btn et-btn--ghost">Search</a>
                @endif
            </div>
        </div>
    </div>
@endsection
