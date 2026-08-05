@php
    $summary = $feedbackSummary ?? ['average' => 0, 'total' => 0, 'breakdown' => [5=>0,4=>0,3=>0,2=>0,1=>0], 'items' => collect()];
    $items = $summary['items'] ?? collect();
    $total = (int) ($summary['total'] ?? 0);
    $average = (float) ($summary['average'] ?? 0);
    $breakdown = $summary['breakdown'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $canLeave = ! empty($canLeaveFeedback);
    $mine = $userFeedback ?? null;
@endphp

<section class="et-card fb-section" id="exam-feedback" aria-labelledby="exam-feedback-title">
    <div class="fb-section__head">
        <div>
            <h2 id="exam-feedback-title">Candidate reviews</h2>
            <p class="fb-section__lead">Ratings and reviews from candidates who completed this exam.</p>
        </div>
        @auth
            @if($canLeave)
                <button
                    type="button"
                    class="et-btn et-btn--primary"
                    data-fb-open-exam-modal
                    aria-haspopup="dialog"
                    aria-controls="fb-exam-modal"
                >
                    Add review
                </button>
            @elseif($mine)
                <span class="et-badge et-badge--success">You already left a review</span>
            @else
                <span class="et-badge et-badge--slate">Available after you submit an attempt</span>
            @endif
        @else
            <a href="{{ route('login', ['redirect' => url()->current().'#exam-feedback']) }}" class="et-btn et-btn--ghost">Login to review</a>
        @endauth
    </div>

    <div class="fb-section__summary">
        <div class="fb-score">
            <strong>{{ $total > 0 ? number_format($average, 1) : '—' }}</strong>
            <div class="fb-score__stars" aria-hidden="true">
                @for($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= round($average) ? 'is-on' : '' }}">★</span>
                @endfor
            </div>
            <em>{{ $total }} review{{ $total === 1 ? '' : 's' }}</em>
        </div>
        <div class="fb-breakdown" aria-label="Rating breakdown">
            @foreach([5,4,3,2,1] as $star)
                @php
                    $count = (int) ($breakdown[$star] ?? 0);
                    $pct = $total > 0 ? (int) round(($count / $total) * 100) : 0;
                @endphp
                <div class="fb-breakdown__row">
                    <span>{{ $star }}★</span>
                    <div class="fb-breakdown__track"><span style="width:{{ $pct }}%"></span></div>
                    <em>{{ $count }}</em>
                </div>
            @endforeach
        </div>
    </div>

    <div class="fb-list">
        @forelse($items as $item)
            <x-feedback-card :feedback="$item" />
        @empty
            <div class="fb-empty">
                <p>No public reviews yet. Be the first to share feedback after your attempt.</p>
            </div>
        @endforelse
    </div>

    @if(method_exists($items, 'hasPages') && $items->hasPages())
        <div class="fb-pagination">
            {{ $items->fragment('exam-feedback')->links('frontend.partials.pagination') }}
        </div>
    @endif
</section>

@auth
    @if($canLeave)
        <div
            class="fb-modal"
            id="fb-exam-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fb-exam-modal-title"
            hidden
        >
            <div class="fb-modal__backdrop" data-fb-close-exam-modal></div>
            <div class="fb-modal__card">
                <h2 id="fb-exam-modal-title">Add your review</h2>
                <p class="fb-modal__lead">Share how this exam felt after your attempt. Your review helps other candidates.</p>
                <x-feedback-form :exam="$exam" source="exam_show">
                    <x-slot:actions>
                        <button type="button" class="et-btn et-btn--ghost" data-fb-close-exam-modal>Cancel</button>
                    </x-slot:actions>
                </x-feedback-form>
            </div>
        </div>
    @endif
@endauth
