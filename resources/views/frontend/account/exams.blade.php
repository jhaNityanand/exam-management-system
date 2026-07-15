@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'My exams'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            <h1>My exams</h1>
            <p>Exams you have started or are eligible to attempt.</p>
        </div>
    </div>

    <div class="et-container et-layout-2">
        @include('frontend.layouts.sidebar')
        <div>
            @if(($exams ?? collect())->isEmpty())
                @include('frontend.partials.empty-state', [
                    'title' => 'No exam history yet',
                    'message' => 'Start a mock test to see it listed here.',
                    'actionUrl' => route('frontend.exams.index'),
                    'actionLabel' => 'Browse exams',
                ])
            @else
                <div class="et-grid et-grid--2">
                    @foreach($exams as $exam)
                        @include('frontend.components.exam-card', ['exam' => $exam])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
