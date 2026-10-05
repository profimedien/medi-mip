<div class="space-y-4">
    <h1 class="text-2xl font-bold">Galerien</h1>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($galleries as $gallery)
            @php $cover = $gallery->getFirstMedia('photos'); @endphp
            <a href="{{ route('galleries.show', $gallery) }}" wire:navigate class="card overflow-hidden p-0 transition hover:ring-medi-sky/40">
                @if ($cover)
                    <img src="{{ route('media.show', [$cover, 'thumb']) }}" alt="" class="aspect-[4/3] w-full object-cover" loading="lazy">
                @else
                    <div class="grid aspect-[4/3] place-items-center bg-medi-bg"><x-heroicon-o-photo class="size-10 text-medi-gray/40" /></div>
                @endif
                <div class="p-4">
                    <div class="font-bold text-medi-navy">{{ $gallery->title }}</div>
                    <div class="text-xs">{{ $gallery->event_date?->translatedFormat('j. F Y') }} · {{ $gallery->getMedia('photos')->count() }} Fotos</div>
                </div>
            </a>
        @empty
            <p class="card text-sm">Noch keine Galerien.</p>
        @endforelse
    </div>
</div>
