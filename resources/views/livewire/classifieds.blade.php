<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Kleinanzeigen</h1>
        <div class="flex rounded-xl bg-white p-1 ring-1 ring-black/5">
            @foreach (['' => 'Alle'] + \App\Models\Classified::CATEGORIES as $k => $v)
                <button wire:click="$set('category', '{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-medi-blue text-white' => $category === $k, 'hover:bg-medi-bg' => $category !== $k])>{{ $v }}</button>
            @endforeach
        </div>
        <button wire:click="$toggle('showForm')" class="btn"><x-heroicon-o-plus class="size-4" /> Anzeige aufgeben</button>
    </div>

    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
        <button wire:click="$set('topic', '')" @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium ring-1', 'bg-medi-blue text-white ring-medi-blue' => $topic === '', 'bg-white ring-black/10 hover:ring-medi-blue/40' => $topic !== ''])>Alle Kategorien</button>
        @foreach ($topics as $t)
            <button wire:click="$set('topic', @js($t))" @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium ring-1', 'bg-medi-blue text-white ring-medi-blue' => $topic === $t, 'bg-white ring-black/10 hover:ring-medi-blue/40' => $topic !== $t])>
                {{ $t }}@if ($counts[$t] ?? 0) <span class="opacity-70">{{ $counts[$t] }}</span>@endif
            </button>
        @endforeach
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card grid max-w-2xl gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Rubrik</label>
                <select wire:model="ad.category" class="input">
                    @foreach (\App\Models\Classified::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">Kategorie</label>
                <select wire:model="ad.topic" class="input">
                    <option value="">Bitte wählen</option>
                    @foreach ($topics as $t)<option>{{ $t }}</option>@endforeach
                </select>
                @error('ad.topic')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2"><label class="label">Preis (optional)</label><input type="text" wire:model="ad.price" class="input" placeholder="z. B. 20 € oder zu verschenken"></div>
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
            <a href="{{ route('classifieds.show', $ad) }}" wire:navigate class="card group flex flex-col overflow-hidden p-0 transition hover:-translate-y-0.5 hover:shadow-md">
                @if ($photo)
                    <img src="{{ route('media.show', [$photo, 'thumb']) }}" alt="" class="aspect-[4/3] w-full object-cover" loading="lazy">
                @else
                    <div class="grid aspect-[4/3] place-items-center bg-gradient-to-br from-medi-blue/10 to-medi-sky/10"><x-heroicon-o-tag class="size-10 text-medi-blue/30" /></div>
                @endif
                <div class="flex flex-1 flex-col p-4">
                    <div class="mb-1 flex items-center gap-2 text-xs">
                        <span class="badge">{{ $ad->topic ?: \App\Models\Classified::CATEGORIES[$ad->category] }}</span>
                        <span>läuft bis {{ $ad->expires_at->format('d.m.') }}</span>
                    </div>
                    <div class="font-bold text-medi-navy group-hover:text-medi-blue">{{ $ad->title }}</div>
                    @if ($ad->price)<div class="text-sm font-medium text-medi-blue">{{ $ad->price }}</div>@endif
                    <p class="mt-1 line-clamp-2 text-sm">{{ $ad->body }}</p>
                    <div class="mt-auto flex items-center gap-2 pt-3 text-xs">
                        <x-avatar :user="$ad->user" class="size-6 text-[10px]" />
                        <span>{{ $ad->user->name }}</span>
                        <span class="ml-auto text-medi-sky">Ansehen</span>
                    </div>
                </div>
            </a>
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
