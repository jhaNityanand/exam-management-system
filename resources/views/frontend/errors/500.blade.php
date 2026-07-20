@extends('frontend.layouts.app')

@section('content')
<div class="et-container et-section" style="max-width:640px;text-align:center;padding-top:4rem">
    <p class="et-badge et-badge--soft">500</p>
    <h1 style="margin:0.75rem 0">Something went wrong</h1>
    <p style="color:var(--et-text-muted)">We hit an unexpected error. Please try again in a moment.</p>
    <div style="margin-top:1.25rem">
        <a href="{{ route('home') }}" class="et-btn et-btn--primary">Go home</a>
    </div>
</div>
@endsection
