@extends('frontend.layouts.app')

@section('content')
@php $avatar = user_avatar($author); @endphp
<div class="et-container et-section">
    <header style="display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:2rem">
        <div style="width:5rem;height:5rem;border-radius:999px;overflow:hidden;background:{{ $avatar['color'] }};color:#fff;display:grid;place-items:center;font-size:1.4rem;font-weight:700;flex-shrink:0">
            @if($avatar['url'])
                <img src="{{ $avatar['url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
            @else
                {{ $avatar['initials'] }}
            @endif
        </div>
        <div style="min-width:0;flex:1">
            <p class="et-badge et-badge--soft">Author</p>
            <h1 style="margin:.45rem 0 .35rem">{{ $author->name }}</h1>
            <p style="color:var(--et-text-muted);margin:0;max-width:42rem">{{ $author->profile?->bio ?: 'Contributor on Examtube.in' }}</p>
        </div>
    </header>

    <section style="margin-bottom:2.5rem">
        <h2 style="margin:0 0 1rem;font-size:1.25rem">Latest blogs</h2>
        @if($blogs->isEmpty())
            <p style="color:var(--et-text-muted)">No published blogs yet.</p>
        @else
            <div style="display:grid;gap:.85rem">
                @foreach($blogs as $blog)
                    <a href="{{ route('frontend.blogs.show', $blog->slug) }}" style="display:block;padding:1rem 1.1rem;border:1px solid var(--et-border);border-radius:var(--et-radius-sm);background:var(--et-surface)">
                        <strong>{{ $blog->title }}</strong>
                        @if($blog->published_at)
                            <span style="display:block;margin-top:.25rem;color:var(--et-text-muted);font-size:.85rem">{{ $blog->published_at->format('M j, Y') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <h2 style="margin:0 0 1rem;font-size:1.25rem">Latest news</h2>
        @if($news->isEmpty())
            <p style="color:var(--et-text-muted)">No published news yet.</p>
        @else
            <div style="display:grid;gap:.85rem">
                @foreach($news as $item)
                    <a href="{{ route('frontend.news.show', $item->slug) }}" style="display:block;padding:1rem 1.1rem;border:1px solid var(--et-border);border-radius:var(--et-radius-sm);background:var(--et-surface)">
                        <strong>{{ $item->title }}</strong>
                        @if($item->published_at)
                            <span style="display:block;margin-top:.25rem;color:var(--et-text-muted);font-size:.85rem">{{ $item->published_at->format('M j, Y') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
