<div class="space-y-4">
    <h1 class="text-2xl font-bold">Verzeichnis</h1>
    <div class="grid gap-2 sm:grid-cols-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name, Funktion, Qualifikation" class="input">
        <select wire:model.live="location" class="input">
            <option value="">Alle Standorte</option>
            @foreach ($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->label }}</option>@endforeach
        </select>
        <select wire:model.live="department" class="input">
            <option value="">Alle Abteilungen</option>
            @foreach ($departments as $dep)<option value="{{ $dep->id }}">{{ $dep->name }}</option>@endforeach
        </select>
    </div>

    @if ($currentDepartment?->description)
        <div class="card text-sm"><h2 class="mb-1 font-bold">{{ $currentDepartment->name }}</h2>{{ $currentDepartment->description }}</div>
    @endif

    <p class="text-xs">{{ $total }} {{ $total === 1 ? 'Person' : 'Personen' }}. Angezeigt werden nur Personen, die ihr Profil freigegeben haben.</p>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($people as $person)
            <a href="{{ route('directory.show', $person) }}" wire:navigate class="card flex items-center gap-3 p-4 transition hover:ring-medi-sky/40">
                <x-avatar :user="$person" class="size-12 text-sm" />
                <span class="min-w-0">
                    <span class="block truncate font-bold text-medi-navy">{{ $person->name }}</span>
                    <span class="block truncate text-xs">{{ $person->job_title }}</span>
                    <span class="block truncate text-xs">{{ $person->location?->label }}</span>
                </span>
            </a>
        @endforeach
    </div>

    @if ($total > $people->count())
        <div class="text-center"><button wire:click="$set('limit', {{ $limit + 30 }})" class="btn-ghost">Weitere laden</button></div>
    @endif
</div>
