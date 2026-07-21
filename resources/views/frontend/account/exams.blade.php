@extends('frontend.account.layout')

@php
    $seo = ['title' => 'My exams'];
@endphp

@section('account-eyebrow', 'Practice history')
@section('account-title', 'My Exams')
@section('account-lead', 'Exams you have started or completed.')

@section('account-actions')
    <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary et-btn--sm">Browse exams</a>
@endsection

@section('account-content')
    @if(($attempts ?? collect())->isEmpty())
        <section class="ca-card">
            @include('frontend.partials.empty-state', [
                'title' => 'No exam history yet',
                'message' => 'Start an assessment to see it listed here.',
                'actionUrl' => route('frontend.exams.index'),
                'actionLabel' => 'Browse exams',
            ])
        </section>
    @else
        <section class="ca-card" style="padding:0;overflow:hidden">
            <div class="ca-table-wrap" style="border:0;border-radius:0">
                <table class="ca-table">
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
                                    <div style="color:var(--et-text-muted);font-size:.8rem">Attempt #{{ $attempt->attempt_no ?: $attempt->id }}</div>
                                </td>
                                <td>
                                    <span class="ca-badge {{ in_array($attempt->status, ['submitted','graded'], true) ? 'is-info' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $attempt->status)) }}
                                    </span>
                                </td>
                                <td>{{ $attempt->percentage !== null ? number_format((float) $attempt->percentage, 1).'%' : '—' }}</td>
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
        </section>
        <div>{{ $attempts->links() }}</div>
    @endif
@endsection
