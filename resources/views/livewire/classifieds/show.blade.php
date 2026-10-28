<div class="mx-auto max-w-4xl space-y-4" x-data="{ current: 0, photos: @js($photos->map(fn ($p) => route('media.show', $p))->values()) }">
    <a href="{{ route('classifieds') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Alle Kleinanzeigen</a>

    <div class="card grid gap-6 p-0 md:grid-cols-5">
        <div class="md:col-span-3">
            @if ($photos->isNotEmpty())
                <div class="relative aspect-[4/3] overflow-hidden bg-medi-bg md:rounded-l-2xl">
                    <img :src="photos[current]" alt="" class="size-full object-cover">
                    <template x-if="photos.length > 1">
                        <div>
                            <button @click="current = (current - 1 + photos.length) % photos.length" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow" aria-label="Vorheriges Foto"><x-heroicon-o-chevron-left class="size-5" /></button>
                            <button @click="current = (current + 1) % photos.length" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow" aria-label="Nächstes Foto"><x-heroicon-o-chevron-right class="size-5" /></button>
                        </div>
                    </template>
                </div>
                @if ($photos->count() > 1)
                    <div class="flex gap-2 p-3">
                        @foreach ($photos as $i => $p)
                            <button @click="current = {{ $i }}" :class="current === {{ $i }} ? 'ring-2 ring-medi-blue' : 'opacity-70'" class="size-16 overflow-hidden rounded-lg">
                                <img src="{{ route('media.show', [$p, 'thumb']) }}" alt="" class="size-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="grid aspect-[4/3] place-items-center bg-gradient-to-br from-medi-blue/10 to-medi-sky/10 md:rounded-l-2xl">
                    <x-heroicon-o-tag class="size-16 text-medi-blue/30" />
                </div>
            @endif
        </div>

        <div class="flex flex-col p-5 md:col-span-2 md:pl-0">
            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                <span class="badge">{{ \App\Models\Classified::CATEGORIES[$classified->category] }}</span>
                @if ($classified->topic)<span class="badge bg-medi-sky/10 text-medi-sky">{{ $classified->topic }}</span>@endif
                <span>eingestellt am {{ $classified->created_at->format('d.m.Y') }}</span>
            </div>
            <h1 class="text-2xl font-bold">{{ $classified->title }}</h1>
            @if ($classified->price)<p class="mt-1 text-xl font-bold text-medi-blue">{{ $classified->price }}</p>@endif
            <p class="mt-4 whitespace-pre-line text-sm">{{ $classified->body }}</p>

            <div class="mt-6 rounded-2xl bg-medi-bg p-4">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$seller" class="size-11 text-sm" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-medi-navy">{{ $seller->name }}</p>
                        <p class="truncate text-xs">{{ $seller->job_title }}{{ $seller->location ? ' · '.$seller->location->label : '' }}</p>
                    </div>
                </div>
                @if ($seller->id !== auth()->id())
                    <a href="{{ route('chat', ['direct' => $seller->id]) }}" wire:navigate class="btn mt-3 w-full"><x-heroicon-o-chat-bubble-left-right class="size-4" /> Nachricht schreiben</a>
                @endif
            </div>

            <p class="mt-3 text-xs">{{ $classified->expires_at->isPast() ? 'Abgelaufen am' : 'Läuft bis' }} {{ $classified->expires_at->format('d.m.Y') }}</p>
            @if ($classified->user_id === auth()->id() || auth()->user()->isAdmin())
                <div class="mt-auto flex gap-3 pt-4 text-sm">
                    @if ($classified->user_id === auth()->id() && ! $classified->extended)<button wire:click="extend" class="text-medi-sky">Um {{ \App\Models\Classified::RUNTIME_DAYS }} Tage verlängern</button>@endif
                    <button x-on:click="$store.confirm.ask('Anzeige wirklich löschen?', () => $wire.remove())" class="text-medi-red">Löschen</button>
                </div>
            @endif
        </div>
    </div>

    @if ($more->isNotEmpty())
        <h2 class="pt-2 font-bold">Weitere Anzeigen</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($more as $ad)
                <a href="{{ route('classifieds.show', $ad) }}" wire:navigate class="card p-4 transition hover:-translate-y-0.5">
                    <span class="block truncate font-medium text-medi-navy">{{ $ad->title }}</span>
                    <span class="text-sm text-medi-blue">{{ $ad->price }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
