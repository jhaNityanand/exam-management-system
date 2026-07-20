@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'My exams'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            <h1>My exams</h1>
            <p>Exams you have started or completed.</p>
        </div>
    </div>

    <div class="et-container et-layout-2">
        @include('frontend.layouts.sidebar')
        <div>
            @if(($attempts ?? collect())->isEmpty())
                @include('frontend.partials.empty-state', [
                    'title' => 'No exam history yet',
                    'message' => 'Start an assessment to see it listed here.',
                    'actionUrl' => route('frontend.exams.index'),
                    'actionLabel' => 'Browse exams',
                ])
            @else
                <div class="et-card" style="padding:0;overflow:auto">
                    <table class="et-table">
                        <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Started</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td>
                                    <strong>{{ $attempt->exam?->title ?: 'Exam' }}</strong>
                                    <div style="color:var(--et-text-muted);font-size:.85rem">Attempt #{{ $attempt->attempt_no ?: $attempt->id }}</div>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $attempt->status)) }}</td>
                                <td>{{ $attempt->percentage !== null ? $attempt->percentage.'%' : '—' }}</td>
                                <td>{{ optional($attempt->started_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    @if(in_array($attempt->status, ['active', 'in_progress'], true) && $attempt->exam)
                                        <a class="et-btn et-btn--primary et-btn--sm" href="{{ route('frontend.exams.started', $attempt->exam) }}">Continue</a>
                                    @else
                                        <a class="et-btn et-btn--ghost et-btn--sm" href="{{ route('frontend.attempts.result', $attempt) }}">Result</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1rem">{{ $attempts->links() }}</div>
            @endif
        </div>
    </div>
@endsection
