@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Account settings'];
    $user = $user ?? auth()->user();
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            <h1>Settings</h1>
            <p>Manage your profile preferences.</p>
        </div>
    </div>

    <div class="et-container et-layout-2">
        @include('frontend.layouts.sidebar')
        <div class="et-card" style="padding:1.25rem">
            @if(session('success'))
                <div class="et-alert et-alert--success">{{ session('success') }}</div>
            @endif

            <form class="et-form" method="POST" action="{{ url()->current() }}">
                @csrf
                @method('PUT')
                <label>
                    Name
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
                </label>
                <p style="margin:0;color:var(--et-text-muted);font-size:.88rem">
                    Password changes are handled from the secure profile page when available.
                </p>
                <button type="submit" class="et-btn et-btn--primary">Save changes</button>
            </form>
        </div>
    </div>
@endsection
