<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Kalender</h1>
        <div class="flex items-center gap-1 rounded-xl bg-white p-1 ring-1 ring-black/5">
            <button wire:click="shift(-1)" class="rounded-lg p-1.5 hover:bg-medi-bg" aria-label="Vormonat"><x-heroicon-o-chevron-left class="size-5" /></button>
            <span class="w-36 text-center text-sm font-medium text-medi-navy">{{ $start->translatedFormat('F Y') }}</span>
            <button wire:click="shift(1)" class="rounded-lg p-1.5 hover:bg-medi-bg" aria-label="Nächster Monat"><x-heroicon-o-chevron-right class="size-5" /></button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 text-sm">
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="event" class="rounded text-medi-blue"> <span class="size-2.5 rounded-full bg-medi-blue"></span> Termine</label>
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="anniversary" class="rounded text-emerald-600"> <span class="size-2.5 rounded-full bg-emerald-600"></span> Jubiläen</label>
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="birthday" class="rounded text-orange-500"> <span class="size-2.5 rounded-full bg-orange-500"></span> Geburtstage</label>
        <select wire:model.live="location" class="input ml-auto w-auto py-1.5">
            <option value="mine">Mein Standort</option>
            <option value="all">Alle Standorte</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->label }}</option>
            @endforeach
        </select>
    </div>
    <p class="text-xs">Termine gelten je nach Zielgruppe. Geburtstage und Jubiläen zeigen wir nur für den gewählten Standort.</p>

    <div class="card divide-y divide-black/5 py-2">
        @forelse ($days as $day => $entries)
            <div class="py-1">
                @foreach ($entries as $entry)
                    @include('livewire.partials.calendar-entry', ['entry' => $entry])
                @endforeach
            </div>
        @empty
            <p class="py-6 text-center text-sm">In diesem Monat gibt es keine Einträge.</p>
        @endforelse
    </div>
</div>
