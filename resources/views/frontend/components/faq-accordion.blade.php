<div class="et-faq" data-faq>
    @foreach(($faqs ?? collect()) as $faq)
        @php $panelId = 'faq-panel-'.$faq->id; @endphp
        <div class="et-faq__item" data-faq-item data-faq-text="{{ \Illuminate\Support\Str::lower($faq->question.' '.$faq->answer) }}">
            <button
                type="button"
                class="et-faq__trigger"
                data-faq-trigger
                aria-expanded="false"
                aria-controls="{{ $panelId }}"
                id="faq-trigger-{{ $faq->id }}"
            >
                <span>{{ $faq->question }}</span>
                <span class="et-faq__icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </span>
            </button>
            <div class="et-faq__panel" id="{{ $panelId }}" role="region" aria-labelledby="faq-trigger-{{ $faq->id }}">
                {!! nl2br(e($faq->answer)) !!}
            </div>
        </div>
    @endforeach
</div>
