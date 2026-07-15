@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'My results'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            <h1>Results</h1>
            <p>Scores and attempt history for completed exams.</p>
        </div>
    </div>

    <div class="et-container et-layout-2">
        @include('frontend.layouts.sidebar')
        <div>
            @if(($results ?? collect())->isEmpty())
                @include('frontend.partials.empty-state', [
                    'title' => 'No results yet',
                    'message' => 'Complete an exam to unlock your scorecard.',
                    'actionUrl' => route('frontend.exams.index'),
                    'actionLabel' => 'Practice now',
                ])
            @else
                <div class="et-card" style="overflow:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem">
                        <thead>
                            <tr style="text-align:left;border-bottom:1px solid var(--et-border)">
                                <th style="padding:.85rem 1rem">Exam</th>
                                <th style="padding:.85rem 1rem">Score</th>
                                <th style="padding:.85rem 1rem">Status</th>
                                <th style="padding:.85rem 1rem">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr style="border-bottom:1px solid var(--et-border)">
                                    <td style="padding:.85rem 1rem">{{ $result->exam->title ?? ($result->title ?? 'Exam') }}</td>
                                    <td style="padding:.85rem 1rem">{{ $result->score ?? $result->percentage ?? '—' }}</td>
                                    <td style="padding:.85rem 1rem">{{ $result->status ?? '—' }}</td>
                                    <td style="padding:.85rem 1rem">{{ optional($result->submitted_at ?? $result->created_at)->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
