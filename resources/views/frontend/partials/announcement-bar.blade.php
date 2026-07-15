@forelse(($announcements ?? collect()) as $announcement)
    <div
        class="et-announce"
        data-announce
        data-announce-id="{{ $announcement->id }}"
        data-announce-type="{{ $announcement->type ?? 'info' }}"
    >
        <div class="et-container et-announce__inner">
            <div class="et-announce__content">
                @if($announcement->title)
                    <span class="et-announce__title">{{ $announcement->title }}</span>
                @endif
                @if($announcement->message)
                    <span class="et-announce__msg">{{ $announcement->message }}</span>
                @endif
                @if(($announcement->cta_label ?? null) && ($announcement->cta_url ?? null))
                    <a class="et-announce__cta" href="{{ $announcement->cta_url }}">{{ $announcement->cta_label }}</a>
                @endif
            </div>
            @if($announcement->is_dismissible ?? true)
                <button type="button" class="et-announce__dismiss" data-announce-dismiss aria-label="Dismiss announcement">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>
@empty
@endforelse
