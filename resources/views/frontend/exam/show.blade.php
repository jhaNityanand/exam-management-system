@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $exam->meta_title ?: $exam->title,
        'description' => $exam->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $exam->description), 160),
        'keywords' => $exam->meta_keywords,
        'canonical' => $exam->canonical_url ?: url()->current(),
        'og_title' => $exam->og_title,
        'og_description' => $exam->og_description,
        'image' => $exam->bannerUrl(),
    ];
    $isFree = ! $exam->isPaid();
    $attemptsLabel = ($exam->attempt_limit_type === 'unlimited' || (int) ($exam->max_attempts ?? 0) === 0)
        ? 'Unlimited'
        : (($exam->attempt_limit_type === 'once') ? '1' : (string) (int) $exam->max_attempts);
    $formats = collect($exam->exam_format ?? [])->map(fn ($f) => str_replace('_', ' ', ucfirst((string) $f)))->implode(', ');
    $returnUrl = route('frontend.exams.rules', $exam);
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Exams', 'url' => route('frontend.exams.index')],
                ['label' => $exam->title],
            ]])
            <div class="et-card__meta" style="margin-bottom:.5rem">
                @if($exam->category)
                    <span class="et-badge">{{ $exam->category->name }}</span>
                @endif
                @if($exam->difficulty_level)
                    <span class="et-badge et-badge--slate">{{ ucfirst($exam->difficulty_level) }}</span>
                @endif
                <span class="et-badge">{{ $isFree ? 'Free' : 'Paid' }}</span>
                <span class="et-badge et-badge--slate">{{ ucfirst(str_replace('_', ' ', (string) $exam->visibility)) }}</span>
            </div>
            <h1>{{ $exam->title }}</h1>
            @if($exam->short_description ?? false)
                <p>{{ $exam->short_description }}</p>
            @elseif($exam->description)
                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $exam->description), 160) }}</p>
            @endif
        </div>
    </div>

    <div class="et-container" style="padding:1.5rem 0 3rem;display:grid;gap:1.25rem">
        @if($exam->bannerUrl())
            <div class="et-card" style="overflow:hidden;padding:0">
                <img src="{{ $exam->bannerUrl() }}" alt="{{ $exam->title }}" style="width:100%;max-height:320px;object-fit:cover;display:block">
            </div>
        @endif

        <div class="et-grid et-grid--4">
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->duration ?? 0) }}</span><span class="et-stat__label">Minutes</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_questions ?? 0) }}</span><span class="et-stat__label">Questions</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_marks ?? 0) }}</span><span class="et-stat__label">Total marks</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->passing_marks ?? 0) }}</span><span class="et-stat__label">Passing marks</span></div>
        </div>

        <div class="et-card" style="padding:1.25rem">
            <h2 style="margin-top:0">Exam details</h2>
            <div class="et-grid et-grid--2" style="gap:.75rem 1.25rem">
                <div><strong>Mode:</strong> {{ ucfirst((string) $exam->exam_mode) }}</div>
                <div><strong>Question types:</strong> {{ $formats ?: '—' }}</div>
                <div><strong>Pricing:</strong>
                    @if($isFree)
                        Free
                    @else
                        {{ strtoupper((string) ($exam->exam_currency ?: 'INR')) }} {{ number_format((float) $exam->exam_amount, 2) }}
                    @endif
                </div>
                <div><strong>Attempts allowed:</strong> {{ $attemptsLabel }}</div>
                <div><strong>Language:</strong> {{ strtoupper((string) ($exam->language ?: 'en')) }}</div>
                <div><strong>Timezone:</strong> {{ $exam->timezone ?: config('app.timezone') }}</div>
                <div><strong>Schedule:</strong>
                    @if(($exam->schedule_type ?? 'any_time') === 'fixed_window')
                        {{ optional($exam->scheduled_start)->format('d M Y H:i') ?: '—' }}
                        —
                        {{ optional($exam->scheduled_end)->format('d M Y H:i') ?: '—' }}
                    @else
                        Available any time
                    @endif
                </div>
                <div><strong>Registration deadline:</strong>
                    {{ optional($exam->registration_deadline)->format('d M Y H:i') ?: 'None' }}
                </div>
            </div>
        </div>

        <div class="et-card" style="padding:1.25rem">
            <h2 style="margin-top:0">About this exam</h2>
            <div class="et-prose">
                @if($exam->description)
                    <x-rich-text-content :content="$exam->description" />
                @else
                    <p>No description provided.</p>
                @endif
            </div>

            <div style="margin-top:1.25rem;display:flex;gap:.65rem;flex-wrap:wrap" id="exam-cta"
                 data-return-url="{{ $returnUrl }}">
                @guest
                    <a href="{{ route('login', ['redirect' => $returnUrl]) }}"
                       class="et-btn et-btn--primary js-store-return"
                       data-return-url="{{ $returnUrl }}">Login</a>
                    <a href="{{ route('register', ['redirect' => $returnUrl]) }}"
                       class="et-btn et-btn--ghost js-store-return"
                       data-return-url="{{ $returnUrl }}">Register</a>
                    @if(! $isFree)
                        <a href="{{ route('login', ['redirect' => $returnUrl]) }}"
                           class="et-btn et-btn--ghost js-store-return"
                           data-return-url="{{ $returnUrl }}">Purchase Exam</a>
                    @endif
                @else
                    @if(! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']))
                        <a href="{{ route('frontend.exams.started', $exam) }}" class="et-btn et-btn--primary">Continue Exam</a>
                    @elseif(empty($evaluation['requires_payment']))
                        <a href="{{ route('frontend.exams.rules', $exam) }}" class="et-btn et-btn--primary">Attempt Exam</a>
                    @endif

                    @if(! empty($evaluation['requires_payment']))
                        <button type="button" class="et-btn et-btn--primary" id="purchase-exam-btn"
                                data-url="{{ route('frontend.exams.purchase', $exam) }}">Purchase Exam</button>
                    @endif

                    @if($previousAttempts->isNotEmpty())
                        <a href="#previous-attempts" class="et-btn et-btn--ghost">View Previous Attempts</a>
                    @endif
                @endauth
            </div>

            @auth
                @if(! empty($evaluation['reasons']))
                    <ul style="margin:1rem 0 0;color:var(--et-text-muted);font-size:.9rem">
                        @foreach($evaluation['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            @endauth
        </div>

        @auth
            @if($previousAttempts->isNotEmpty())
                <div class="et-card" style="padding:1.25rem" id="previous-attempts">
                    <h2 style="margin-top:0">Previous attempts</h2>
                    <div style="overflow:auto">
                        <table class="et-table">
                            <thead>
                            <tr>
                                <th>Attempt</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Submitted</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($previousAttempts as $attempt)
                                <tr>
                                    <td>#{{ $attempt->id }}</td>
                                    <td>{{ ucfirst($attempt->status) }}</td>
                                    <td>{{ $attempt->percentage !== null ? $attempt->percentage.'%' : '—' }}</td>
                                    <td>{{ optional($attempt->submitted_at)->format('d M Y H:i') ?: '—' }}</td>
                                    <td><a href="{{ route('frontend.attempts.result', $attempt) }}">View</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endauth
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const key = 'ems_exam_return_url';
    document.querySelectorAll('.js-store-return').forEach((el) => {
        el.addEventListener('click', () => {
            const url = el.getAttribute('data-return-url');
            if (!url) return;
            try { localStorage.setItem(key, url); } catch (e) {}
            document.cookie = 'ems_exam_return_url=' + encodeURIComponent(url) + '; path=/; max-age=7200; SameSite=Lax';
        });
    });

    const purchaseBtn = document.getElementById('purchase-exam-btn');
    if (purchaseBtn) {
        purchaseBtn.addEventListener('click', async () => {
            if (!confirm('Proceed with placeholder payment for this exam?')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch(purchaseBtn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            });
            if (res.ok) {
                window.location.href = @json(route('frontend.exams.rules', $exam));
            } else {
                alert('Unable to complete placeholder payment.');
            }
        });
    }
})();
</script>
@endpush
