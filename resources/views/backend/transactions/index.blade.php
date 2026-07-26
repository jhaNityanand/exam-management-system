@extends('backend.layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Transactions'],
    ]" />
@endsection

@section('content')
<div class="txn-coming-soon">
    <section class="txn-coming-soon__card">
        <div class="txn-coming-soon__badge">Coming Soon</div>

        <div class="txn-coming-soon__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>

        <h1 class="txn-coming-soon__title">Transactions</h1>
        <p class="txn-coming-soon__desc">
            The Transactions module is currently under development and will be available soon.
        </p>

        <div class="txn-coming-soon__meta">
            <span>Payments</span>
            <span>Invoices</span>
            <span>Refunds</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="panel-button-primary">
            Back to Dashboard
        </a>
    </section>
</div>
@endsection

@push('styles')
<style>
.txn-coming-soon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: min(70vh, 36rem);
    padding: 1.5rem 0.75rem;
}

.txn-coming-soon__card {
    width: min(100%, 34rem);
    text-align: center;
    padding: 2.25rem 1.75rem;
    border-radius: 1.5rem;
    border: 1px solid rgb(226 232 240 / 0.9);
    background:
        radial-gradient(circle at top left, rgb(99 102 241 / 0.08), transparent 40%),
        radial-gradient(circle at bottom right, rgb(14 165 233 / 0.08), transparent 36%),
        #fff;
    box-shadow: 0 20px 45px -36px rgb(15 23 42 / 0.3);
}

.dark .txn-coming-soon__card {
    border-color: rgb(30 41 59);
    background:
        radial-gradient(circle at top left, rgb(99 102 241 / 0.16), transparent 40%),
        radial-gradient(circle at bottom right, rgb(16 185 129 / 0.1), transparent 36%),
        rgb(15 23 42);
}

.txn-coming-soon__badge {
    display: inline-flex;
    align-items: center;
    margin-bottom: 1.25rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgb(67 56 202);
    background: rgb(238 242 255);
    border: 1px solid rgb(199 210 254);
}

.dark .txn-coming-soon__badge {
    color: rgb(165 180 252);
    background: rgb(79 70 229 / 0.18);
    border-color: rgb(129 140 248 / 0.4);
}

.txn-coming-soon__icon {
    width: 5.5rem;
    height: 5.5rem;
    margin: 0 auto 1.25rem;
    display: grid;
    place-items: center;
    border-radius: 1.35rem;
    color: rgb(79 70 229);
    background: rgb(238 242 255);
    border: 1px solid rgb(199 210 254 / 0.8);
}

.dark .txn-coming-soon__icon {
    color: rgb(165 180 252);
    background: rgb(79 70 229 / 0.18);
    border-color: rgb(129 140 248 / 0.35);
}

.txn-coming-soon__icon svg {
    width: 2.5rem;
    height: 2.5rem;
}

.txn-coming-soon__title {
    margin: 0;
    font-size: clamp(1.6rem, 3vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: rgb(15 23 42);
}

.dark .txn-coming-soon__title {
    color: rgb(248 250 252);
}

.txn-coming-soon__desc {
    margin: 0.75rem auto 0;
    max-width: 28rem;
    font-size: 0.95rem;
    line-height: 1.6;
    color: rgb(100 116 139);
}

.dark .txn-coming-soon__desc {
    color: rgb(148 163 184);
}

.txn-coming-soon__meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    margin: 1.35rem 0 1.5rem;
}

.txn-coming-soon__meta span {
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: rgb(71 85 105);
    background: rgb(248 250 252);
    border: 1px solid rgb(226 232 240);
}

.dark .txn-coming-soon__meta span {
    color: rgb(203 213 225);
    background: rgb(2 6 23 / 0.45);
    border-color: rgb(51 65 85);
}
</style>
@endpush
