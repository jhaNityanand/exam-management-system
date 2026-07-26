@props([
    'exam',
    'attemptId' => null,
    'storeUrl' => null,
    'source' => 'exam_show',
    'compact' => false,
])

@php
    $storeUrl = $storeUrl ?: route('frontend.feedback.store');
@endphp

<form class="fb-form{{ $compact ? ' fb-form--compact' : '' }}"
      data-fb-form
      data-store-url="{{ $storeUrl }}"
      data-exam-id="{{ $exam->id }}"
      data-attempt-id="{{ $attemptId ?: '' }}"
      data-source="{{ $source }}"
      novalidate>
    @csrf
    <div class="fb-form__stars" data-fb-stars role="radiogroup" aria-label="Rating">
        @for($i = 1; $i <= 5; $i++)
            <button type="button"
                    class="fb-star"
                    data-fb-star="{{ $i }}"
                    role="radio"
                    aria-checked="false"
                    aria-label="{{ $i }} star{{ $i === 1 ? '' : 's' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3.6 2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.9 6.2 18.5l.9-5.4-3.9-3.8 5.4-.8L12 3.6Z"/></svg>
            </button>
        @endfor
        <input type="hidden" name="rating" value="" data-fb-rating required>
        <span class="fb-form__rating-label" data-fb-rating-label>Select a rating</span>
    </div>

    <div class="fb-field">
        <label for="fb-title-{{ $exam->id }}-{{ $source }}">Title <span>(optional)</span></label>
        <input type="text"
               id="fb-title-{{ $exam->id }}-{{ $source }}"
               name="title"
               maxlength="160"
               placeholder="Summarize your experience"
               data-fb-title>
    </div>

    <div class="fb-field">
        <label for="fb-message-{{ $exam->id }}-{{ $source }}">Your feedback</label>
        <textarea id="fb-message-{{ $exam->id }}-{{ $source }}"
                  name="message"
                  rows="4"
                  maxlength="2000"
                  minlength="10"
                  placeholder="What went well? What could be improved?"
                  data-fb-message
                  required></textarea>
        <div class="fb-field__meta">
            <span class="fb-error" data-fb-error hidden></span>
            <span class="fb-counter"><span data-fb-count>0</span>/2000</span>
        </div>
    </div>

    <div class="fb-form__actions">
        {{ $actions ?? '' }}
        <button type="submit" class="et-btn et-btn--primary" data-fb-submit>
            <span data-fb-submit-label>Submit feedback</span>
        </button>
    </div>
    <p class="fb-success" data-fb-success hidden role="status">Thank you for your feedback!</p>
</form>
