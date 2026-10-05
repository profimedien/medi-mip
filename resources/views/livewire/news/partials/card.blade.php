@php
    $read = $post->readBy(auth()->user());
    $image = $post->getFirstMedia('image');
    $featured = $featured ?? false;
@endphp
<a href="{{ route('news.show', $post) }}" wire:navigate
   @class(['card group block overflow-hidden p-0 transition hover:-translate-y-0.5 hover:shadow-md', 'sm:flex' => ! $featured])>
    @if ($image)
        <div @class(['relative overflow-hidden bg-medi-bg', 'aspect-[16/9]' => $featured, 'aspect-[16/9] sm:aspect-auto sm:w-64 sm:shrink-0' => ! $featured])>
            <img src="{{ route('media.show', [$image, 'card']) }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
            @if ($post->pinned)<span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-0.5 text-xs font-medium text-medi-blue shadow">Angeheftet</span>@endif
        </div>
    @endif
    <div class="flex-1 p-5">
        <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
            <span>{{ $post->published_at->translatedFormat('j. F Y') }}</span>
            @if ($post->pinned && ! $image)<span class="badge">Angeheftet</span>@endif
            @unless ($post->audience_all)<span class="badge bg-medi-sky/10 text-medi-sky">{{ $post->audienceLabel() }}</span>@endunless
            @if ($post->requires_confirmation && ! $read?->confirmed_at)
                <span class="badge bg-medi-red/10 text-medi-red">Bitte bestätigen</span>
            @elseif (! $read)
                <span class="badge bg-medi-red/10 text-medi-red">Neu</span>
            @endif
        </div>
        <h3 @class(['font-bold group-hover:text-medi-blue', 'text-xl' => $featured, 'text-lg' => ! $featured])>{{ $post->title }}</h3>
        @if ($post->teaser)
            <p class="mt-1 line-clamp-3 text-sm">{{ $post->teaser }}</p>
        @endif
    </div>
</a>
