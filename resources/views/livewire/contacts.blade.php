<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Ansprechpartner</h1>
        <div class="flex rounded-xl bg-white p-1 ring-1 ring-black/5">
            @foreach (['' => 'Alle', 'internal' => 'Intern', 'external' => 'Extern'] as $k => $v)
                <button wire:click="$set('type', '{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-medi-blue text-white' => $type === $k, 'hover:bg-medi-bg' => $type !== $k])>{{ $v }}</button>
            @endforeach
        </div>
        <select wire:model.live="location" class="input w-full sm:w-64">
            <option value="alle">Alle Einrichtungen</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->label }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($contacts as $contact)
            <div class="card transition hover:-translate-y-0.5 hover:shadow-md">
                <a href="{{ route('contacts.show', $contact) }}" wire:navigate class="flex items-center gap-3">
                    @if ($p = $contact->getFirstMedia('photo'))
                        <img src="{{ route('media.show', [$p, 'thumb']) }}" alt="" class="size-12 rounded-xl object-cover">
                    @else
                        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-medi-blue/10 font-bold text-medi-blue">{{ $contact->initials() }}</span>
                    @endif
                    <span class="min-w-0">
                        <span class="block font-bold text-medi-navy">{{ $contact->name }}</span>
                        <span class="block text-sm">{{ $contact->responsibility }}</span>
                        @if ($contact->contact_person)<span class="block text-sm text-medi-gray">{{ $contact->contact_person }}</span>@endif
                    </span>
                    <x-heroicon-o-chevron-right class="ml-auto size-5 shrink-0 text-medi-gray/50" />
                </a>
                @if ($contact->locations->isNotEmpty() || $contact->type === 'external')
                    <div class="mt-1 flex flex-wrap gap-1">
                        @if ($contact->type === 'external')<span class="badge">Extern</span>@endif
                        @if ($contact->locations->count() > 2)
                            <span class="badge" title="{{ $contact->locations->pluck('label')->join(', ') }}">{{ $contact->locations->count() }} Einrichtungen</span>
                        @else
                            @foreach ($contact->locations as $loc)<span class="badge">{{ $loc->label }}</span>@endforeach
                        @endif
                    </div>
                @endif
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
