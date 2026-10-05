<article class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('news.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Alle News</a>

    <div class="card space-y-4">
        @if ($image)
            <img src="{{ route('media.show', $image) }}" alt="" class="-mx-5 -mt-5 w-[calc(100%+2.5rem)] max-w-none rounded-t-2xl object-cover">
        @endif
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span>{{ $post->published_at->translatedFormat('j. F Y') }}</span>
            @if ($post->author)<span>· {{ $post->author->name }}</span>@endif
            @unless ($post->audience_all)<span class="badge bg-medi-sky/10 text-medi-sky">{{ $post->audienceLabel() }}</span>@endunless
        </div>
        <h1 class="text-2xl font-bold">{{ $post->title }}</h1>
        <div class="prose-medi">{!! $post->body !!}</div>

        @if ($attachments->isNotEmpty())
            <div class="border-t border-black/5 pt-4">
                <h2 class="mb-2 text-sm font-bold">Anhänge</h2>
                <ul class="space-y-1.5">
                    @foreach ($attachments as $file)
                        <li><a href="{{ route('media.show', $file) }}" target="_blank" class="flex items-center gap-2 text-sm text-medi-sky hover:underline"><x-heroicon-o-paper-clip class="size-4" /> {{ $file->file_name }} <span class="text-xs text-medi-gray">({{ $file->human_readable_size }})</span></a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($post->requires_confirmation)
            <div class="rounded-xl bg-medi-bg p-4">
                @if ($read?->confirmed_at)
                    <p class="flex items-center gap-2 text-sm text-emerald-700"><x-heroicon-o-check-circle class="size-5" /> Gelesen und bestätigt am {{ $read->confirmed_at->format('d.m.Y, H:i') }} Uhr</p>
                @else
                    <p class="mb-3 text-sm">Bitte bestätigen Sie, dass Sie diese Information gelesen haben.</p>
                    <button wire:click="confirm" class="btn">Gelesen und verstanden</button>
                @endif
            </div>
        @endif
    </div>
</article>
