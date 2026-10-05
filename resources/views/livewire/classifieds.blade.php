<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Kleinanzeigen</h1>
        <select wire:model.live="category" class="input w-auto">
            <option value="">Alle</option>
            @foreach (\App\Models\Classified::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
        <button wire:click="$toggle('showForm')" class="btn"><x-heroicon-o-plus class="size-4" /> Anzeige aufgeben</button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card grid max-w-2xl gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Rubrik</label>
                <select wire:model="ad.category" class="input">
                    @foreach (\App\Models\Classified::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">Preis (optional)</label><input type="text" wire:model="ad.price" class="input" placeholder="z. B. 20 € oder zu verschenken"></div>
            <div class="sm:col-span-2">
                <label class="label">Titel</label><input type="text" wire:model="ad.title" class="input">
                @error('ad.title')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label">Beschreibung</label><textarea wire:model="ad.body" rows="4" class="input"></textarea>
                @error('ad.body')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label">Fotos (bis zu 4)</label>
                <input type="file" wire:model="photos" multiple accept="image/*" class="text-sm">
                @error('photos.*')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
            </div>
            <p class="text-xs sm:col-span-2">Die Anzeige läuft {{ \App\Models\Classified::RUNTIME_DAYS }} Tage und kann einmal verlängert werden.</p>
            <div class="sm:col-span-2"><button class="btn" wire:loading.attr="disabled">Veröffentlichen</button></div>
        </form>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($ads as $ad)
            @php $photo = $ad->getFirstMedia('photos'); @endphp
            <div class="card flex flex-col overflow-hidden p-0">
                @if ($photo)<img src="{{ route('media.show', [$photo, 'thumb']) }}" alt="" class="aspect-[4/3] w-full object-cover" loading="lazy">@endif
                <div class="flex flex-1 flex-col p-4">
                    <div class="mb-1 flex items-center gap-2 text-xs">
                        <span class="badge">{{ \App\Models\Classified::CATEGORIES[$ad->category] }}</span>
                        <span>läuft bis {{ $ad->expires_at->format('d.m.') }}</span>
                    </div>
                    <div class="font-bold text-medi-navy">{{ $ad->title }}</div>
                    @if ($ad->price)<div class="text-sm font-medium text-medi-blue">{{ $ad->price }}</div>@endif
                    <p class="mt-1 text-sm">{{ $ad->body }}</p>
                    <div class="mt-auto flex items-center gap-2 pt-3 text-xs">
                        <x-avatar :user="$ad->user" class="size-6 text-[10px]" />
                        <span>{{ $ad->user->name }}</span>
                        @if ($ad->user_id !== auth()->id())
                            <a href="{{ route('chat', ['direct' => $ad->user_id]) }}" wire:navigate class="ml-auto text-medi-sky">Nachricht</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="card text-sm sm:col-span-2">Gerade gibt es keine Anzeigen.</p>
        @endforelse
    </div>

    @if ($mine->isNotEmpty())
        <section class="card">
            <h2 class="mb-2 font-bold">Meine Anzeigen</h2>
            <ul class="divide-y divide-black/5 text-sm">
                @foreach ($mine as $ad)
                    <li class="flex flex-wrap items-center gap-2 py-2">
                        <span class="font-medium text-medi-navy">{{ $ad->title }}</span>
                        <span class="text-xs">{{ $ad->expires_at->isPast() ? 'abgelaufen' : 'bis '.$ad->expires_at->format('d.m.Y') }}</span>
                        <span class="ml-auto flex gap-3">
                            @unless ($ad->extended)<button wire:click="extend({{ $ad->id }})" class="text-medi-sky">Verlängern</button>@endunless
                            <button wire:click="remove({{ $ad->id }})" wire:confirm="Anzeige wirklich löschen?" class="text-medi-red">Löschen</button>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
