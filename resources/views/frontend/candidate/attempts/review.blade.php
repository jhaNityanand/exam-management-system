@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Review — '.$exam->title];
@endphp

@section('content')
<div class="et-page-hero">
    <div class="et-container">
        <h1>Question review</h1>
        <p>{{ $exam->title }}</p>
    </div>
</div>

<div class="et-container" style="padding:1.5rem 0 3rem;display:grid;gap:1rem">
    @foreach($items as $item)
        <div class="et-card" style="padding:1.25rem">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <strong>Q{{ $item['position'] }}</strong>
                <span>
                    @if($item['is_correct'] === true) Correct
                    @elseif($item['is_correct'] === false) Incorrect
                    @else Pending / Manual
                    @endif
                    · Marks: {{ $item['awarded_marks'] ?? 0 }} / {{ $item['marks'] }}
                </span>
            </div>
            <div class="et-prose" style="margin-top:.75rem">
                {!! $item['question']['body'] !!}
            </div>
            <p><strong>Your answer:</strong> {{ is_array($item['candidate_answer']) ? json_encode($item['candidate_answer']) : ($item['candidate_answer'] ?? '—') }}</p>
            <p><strong>Correct answer:</strong> {{ is_array($item['correct_answer']) ? json_encode($item['correct_answer']) : ($item['correct_answer'] ?? '—') }}</p>
            @if(! empty($item['explanation']))
                <div class="et-prose"><strong>Explanation:</strong> {!! $item['explanation'] !!}</div>
            @endif
        </div>
    @endforeach

    <div>
        <a href="{{ route('frontend.attempts.result', $attempt) }}" class="et-btn et-btn--ghost">Back to result</a>
    </div>
</div>
@endsection
