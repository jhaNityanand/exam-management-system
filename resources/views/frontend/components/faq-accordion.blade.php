<div class="et-faq" data-faq>
    @foreach(($faqs ?? collect()) as $faq)
        <div class="et-faq__item" data-faq-item>
            <button
                type="button"
                class="et-faq__trigger"
                data-faq-trigger
                aria-expanded="false"
                id="faq-trigger-{{ $faq->id }}"
            >
                <span>{{ $faq->question }}</span>
                <span class="et-faq__icon" aria-hidden="true">+</span>
            </button>
            <div class="et-faq__panel" role="region" aria-labelledby="faq-trigger-{{ $faq->id }}">
                {!! nl2br(e($faq->answer)) !!}
            </div>
        </div>
    @endforeach
</div>
