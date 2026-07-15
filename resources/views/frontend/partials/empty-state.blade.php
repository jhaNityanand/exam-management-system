<div class="et-empty">
    <h3>{{ $title ?? 'Nothing here yet' }}</h3>
    <p>{{ $message ?? 'Check back soon for new content.' }}</p>
    @if(!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="et-btn et-btn--primary et-btn--sm">{{ $actionLabel }}</a>
    @endif
</div>
