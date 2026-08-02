@extends('frontend.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/account-panel.css') }}">
@endpush

@section('content')
<x-ad-layout page="account">
<div class="ca-page">
    <div class="et-container ca-shell">
        <div class="ca-toolbar">
            <button type="button" class="ca-toolbar__menu et-btn et-btn--ghost et-btn--sm" data-ca-sidebar-open aria-controls="ca-sidebar" aria-expanded="false">
                Menu
            </button>
            <div class="ca-toolbar__copy">
                <p class="ca-eyebrow">@yield('account-eyebrow', 'Candidate account')</p>
                <h1>@yield('account-title', 'Account')</h1>
                @hasSection('account-lead')
                    <p class="ca-lead">@yield('account-lead')</p>
                @endif
            </div>
            <div class="ca-toolbar__actions">
                @yield('account-actions')
            </div>
        </div>

        <x-ad-slot page="account" position="below_title" />

        <div class="ca-layout">
            <div class="ca-sidebar-backdrop" data-ca-sidebar-close hidden></div>
            @include('frontend.layouts.sidebar')
            <div class="ca-main" id="account-content" role="region" aria-label="Account content">
                @yield('account-content')
                <x-ad-slot page="account" position="after_content" />
            </div>
        </div>
    </div>
</div>
</x-ad-layout>
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/frontend/account-panel.js') }}" defer></script>
@stack('account-scripts')
@endpush
