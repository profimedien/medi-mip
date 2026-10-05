<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Ansprechpartner</h1>
        <select wire:model.live="location" class="input w-full sm:w-64">
            <option value="">Alle Standorte</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->label }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($contacts as $contact)
            <div class="card">
                <div class="font-bold text-medi-navy">{{ $contact->name }}</div>
                <div class="text-sm">{{ $contact->responsibility }}</div>
                @if ($contact->location)<div class="mt-1"><span class="badge">{{ $contact->location->label }}</span></div>@endif
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($contact->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->phone) }}" class="btn-ghost py-1.5"><x-heroicon-o-phone class="size-4" /> {{ $contact->phone }}</a>@endif
                    @if ($contact->email)<a href="mailto:{{ $contact->email }}" class="btn-ghost py-1.5"><x-heroicon-o-envelope class="size-4" /> E-Mail</a>@endif
                </div>
            </div>
        @empty
            <p class="card text-sm">Noch keine Ansprechpartner hinterlegt.</p>
        @endforelse
    </div>
</div>
