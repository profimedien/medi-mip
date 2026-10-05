@php $read = $post->readBy(auth()->user()); @endphp
<a href="{{ route('news.show', $post) }}" wire:navigate class="card block transition hover:ring-medi-sky/40">
    <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
        <span>{{ $post->published_at->translatedFormat('j. F Y') }}</span>
        @if ($post->pinned)<span class="badge">Angeheftet</span>@endif
        @unless ($post->audience_all)<span class="badge bg-medi-sky/10 text-medi-sky">{{ $post->audienceLabel() }}</span>@endunless
        @if ($post->requires_confirmation && ! $read?->confirmed_at)
            <span class="badge bg-medi-red/10 text-medi-red">Bitte bestätigen</span>
        @elseif (! $read)
            <span class="badge bg-medi-red/10 text-medi-red">Neu</span>
        @endif
    </div>
    <h3 class="text-lg font-bold">{{ $post->title }}</h3>
    @if ($post->teaser)
        <p class="mt-1 text-sm">{{ $post->teaser }}</p>
    @endif
</a>
