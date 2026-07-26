@extends('frontend.layouts.app')

@section('content')
<div class="et-container et-section">
    <header class="et-page-header" style="margin-bottom:1.5rem">
        <p class="et-badge et-badge--soft">Authors</p>
        <h1 style="margin:0.5rem 0 0.35rem">Meet our authors</h1>
        <p style="color:var(--et-text-muted);margin:0;max-width:40rem">Application and organization admins who publish exams, blogs, and news on Examtube.</p>
    </header>

    @if($authors->isEmpty())
        <p style="color:var(--et-text-muted)">No public authors are available yet.</p>
    @else
        <div class="et-card-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
            @foreach($authors as $author)
                @php $avatar = user_avatar($author); @endphp
                <a href="{{ route('frontend.authors.show', $author->slug) }}" class="et-surface" style="display:block;padding:1.25rem;border-radius:var(--et-radius);border:1px solid var(--et-border);background:var(--et-surface);transition:transform .16s ease,box-shadow .16s ease">
                    <div style="display:flex;align-items:center;gap:.85rem">
                        <div style="width:3rem;height:3rem;border-radius:999px;overflow:hidden;background:{{ $avatar['color'] }};color:#fff;display:grid;place-items:center;font-weight:700">
                            @if($avatar['url'])
                                <img src="{{ $avatar['url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
                            @else
                                {{ $avatar['initials'] }}
                            @endif
                        </div>
                        <div>
                            <strong style="display:block">{{ $author->name }}</strong>
                            <span style="color:var(--et-text-muted);font-size:.875rem">{{ Str::limit($author->profile?->bio ?? 'Examtube contributor', 60) }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
