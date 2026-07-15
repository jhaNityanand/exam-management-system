@php $partners = $page['partners'] ?? collect(); @endphp
<section class="et-section et-section--alt">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
        ])
        @if($partners->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'Partners coming soon', 'message' => ''])
        @else
            <div class="et-partners">
                @foreach($partners as $partner)
                    @php $href = $partner->url ?: '#'; @endphp
                    <a class="et-partner" href="{{ $href }}" @if($partner->url) target="_blank" rel="noopener noreferrer" @endif>
                        @if($partner->logo && $partner->logo->file_url)
                            <img src="{{ $partner->logo->file_url }}" alt="{{ $partner->name }}">
                        @else
                            {{ $partner->name }}
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
