@extends('frontend.account.layout')

@php
    $seo = ['title' => 'My results'];
@endphp

@section('account-eyebrow', 'Scorecards')
@section('account-title', 'Results')
@section('account-lead', 'Scores and attempt history for completed exams.')

@section('account-content')
    @if(($results ?? collect())->isEmpty())
        <section class="ca-card">
            @include('frontend.partials.empty-state', [
                'title' => 'No results yet',
                'message' => 'Complete an exam to unlock your scorecard.',
                'actionUrl' => route('frontend.exams.index'),
                'actionLabel' => 'Practice now',
            ])
        </section>
    @else
        <section class="ca-card" style="padding:0;overflow:hidden">
            <div class="ca-table-wrap" style="border:0;border-radius:0">
                <table class="ca-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td><strong>{{ $result->exam->title ?? 'Exam' }}</strong></td>
                                <td>{{ $result->score !== null ? number_format((float) $result->score, 2) : '—' }}</td>
                                <td>{{ $result->percentage !== null ? number_format((float) $result->percentage, 1).'%' : '—' }}</td>
                                <td>
                                    @if($result->passed === true)
                                        <span class="ca-badge is-pass">Pass</span>
                                    @elseif($result->passed === false)
                                        <span class="ca-badge is-fail">Fail</span>
                                    @else
                                        <span class="ca-badge">{{ ucfirst(str_replace('_', ' ', (string) $result->status)) }}</span>
                                    @endif
                                </td>
                                <td>{{ optional($result->submitted_at)->format('d M Y') ?: '—' }}</td>
                                <td>
                                    <a class="et-btn et-btn--ghost et-btn--sm" href="{{ route('frontend.attempts.result', $result) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div>{{ $results->links() }}</div>
    @endif
@endsection
