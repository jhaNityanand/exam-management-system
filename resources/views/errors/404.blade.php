@extends('frontend.layouts.app')

@section('content')
<div class="et-container et-section" style="max-width:640px;text-align:center;padding-top:4rem">
    <p class="et-badge et-badge--soft">404</p>
    <h1 style="margin:0.75rem 0">Page not found</h1>
    <p style="color:var(--et-text-muted)">The page you are looking for does not exist or may have moved.</p>
    <div style="display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap;margin-top:1.25rem">
        <a href="{{ route('home') }}" class="et-btn et-btn--primary">Go home</a>
        <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--ghost">Browse exams</a>
    </div>
</div>
@endsection
