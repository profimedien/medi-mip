<div class="space-y-4" x-data="{ open: null, photos: @js($photos->map(fn ($p) => route('media.show', [$p, 'large']))->values()) }">
    <a href="{{ route('galleries.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Alle Galerien</a>
    <div>
        <h1 class="text-2xl font-bold">{{ $gallery->title }}</h1>
        @if ($gallery->description)<p class="text-sm">{{ $gallery->description }}</p>@endif
    </div>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
        @foreach ($photos as $i => $photo)
            <button type="button" @click="open = {{ $i }}" class="overflow-hidden rounded-xl">
                <img src="{{ route('media.show', [$photo, 'thumb']) }}" alt="" class="aspect-square w-full object-cover transition hover:scale-105" loading="lazy">
            </button>
        @endforeach
    </div>
    <div x-cloak x-show="open !== null" class="fixed inset-0 z-50 grid place-items-center bg-black/90 p-4" @keydown.escape.window="open = null" @click.self="open = null">
        <img :src="photos[open]" alt="" class="max-h-[85vh] max-w-full rounded-lg">
        <button @click="open = null" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white" aria-label="Schließen"><x-heroicon-o-x-mark class="size-6" /></button>
        <button x-show="open > 0" @click="open--" class="absolute left-2 top-1/2 rounded-full bg-white/10 p-2 text-white" aria-label="Zurück"><x-heroicon-o-chevron-left class="size-6" /></button>
        <button x-show="open < photos.length - 1" @click="open++" class="absolute right-2 top-1/2 rounded-full bg-white/10 p-2 text-white" aria-label="Weiter"><x-heroicon-o-chevron-right class="size-6" /></button>
    </div>
</div>
