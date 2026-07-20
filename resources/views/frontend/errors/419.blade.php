@extends('frontend.layouts.app')

@section('content')
<div class="et-container et-section" style="max-width:640px;text-align:center;padding-top:4rem">
    <p class="et-badge et-badge--soft">419</p>
    <h1 style="margin:0.75rem 0">Session expired</h1>
    <p style="color:var(--et-text-muted)">Your session has expired. Please refresh and try again.</p>
    <div style="margin-top:1.25rem">
        <a href="{{ url()->previous() ?: route('home') }}" class="et-btn et-btn--primary">Go back</a>
    </div>
</div>
@endsection
