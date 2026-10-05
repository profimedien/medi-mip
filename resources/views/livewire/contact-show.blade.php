<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('contacts') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Alle Ansprechpartner</a>

    <div class="card overflow-hidden p-0">
        <div class="h-24 bg-gradient-to-r from-medi-blue to-medi-navy"></div>
        <div class="-mt-12 px-6 pb-6">
            @if ($photo)
                <img src="{{ route('media.show', [$photo, 'thumb']) }}" alt="" class="size-24 rounded-2xl object-cover ring-4 ring-white">
            @else
                <span class="grid size-24 place-items-center rounded-2xl bg-white text-2xl font-bold text-medi-blue ring-4 ring-white shadow">{{ $contact->initials() }}</span>
            @endif
            <h1 class="mt-3 text-2xl font-bold">{{ $contact->name }}</h1>
            <p class="font-medium text-medi-blue">{{ $contact->responsibility }}</p>
            @if ($contact->location)<p class="mt-1 text-sm">{{ $contact->location->name }}</p>@endif

            @if ($contact->description)
                <p class="mt-4 whitespace-pre-line text-sm">{{ $contact->description }}</p>
            @endif

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @if ($contact->phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->phone) }}" class="flex items-center gap-3 rounded-2xl bg-medi-bg p-4 transition hover:bg-medi-blue/5">
                        <span class="grid size-10 place-items-center rounded-xl bg-medi-blue text-white"><x-heroicon-o-phone class="size-5" /></span>
                        <span><span class="block text-xs">Anrufen</span><span class="font-medium text-medi-navy">{{ $contact->phone }}</span></span>
                    </a>
                @endif
                @if ($contact->email)
                    <a href="mailto:{{ $contact->email }}" class="flex items-center gap-3 rounded-2xl bg-medi-bg p-4 transition hover:bg-medi-blue/5">
                        <span class="grid size-10 place-items-center rounded-xl bg-medi-sky text-white"><x-heroicon-o-envelope class="size-5" /></span>
                        <span class="min-w-0"><span class="block text-xs">E-Mail schreiben</span><span class="block truncate font-medium text-medi-navy">{{ $contact->email }}</span></span>
                    </a>
                @endif
            </div>
            @if ($contact->availability)
                <p class="mt-4 flex items-center gap-2 text-sm"><x-heroicon-o-clock class="size-4 text-medi-blue" /> Erreichbar: {{ $contact->availability }}</p>
            @endif
        </div>
    </div>

    @if ($others->isNotEmpty())
        <h2 class="pt-2 font-bold">Weitere Ansprechpartner</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($others as $o)
                <a href="{{ route('contacts.show', $o) }}" wire:navigate class="card flex items-center gap-3 p-4 transition hover:-translate-y-0.5">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-medi-blue/10 text-sm font-bold text-medi-blue">{{ $o->initials() }}</span>
                    <span class="min-w-0"><span class="block truncate font-medium text-medi-navy">{{ $o->name }}</span><span class="block truncate text-xs">{{ $o->responsibility }}</span></span>
                </a>
            @endforeach
        </div>
    @endif
</div>
