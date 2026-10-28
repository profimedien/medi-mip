<div class="mx-auto max-w-2xl space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Mitteilungen</h1>
        <button wire:click="readAll" class="text-sm text-medi-sky">Alle als gelesen markieren</button>
    </div>
    <div class="card divide-y divide-black/5 p-0">
        @forelse ($items as $n)
            <button wire:click="open('{{ $n->id }}')" @class(['flex w-full gap-3 px-4 py-3 text-left text-sm hover:bg-medi-bg', 'bg-medi-blue/5' => ! $n->read_at])>
                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-medi-red' => ! $n->read_at, 'bg-transparent' => $n->read_at])></span>
                <span class="min-w-0">
                    <span class="block font-medium text-medi-navy">{{ $n->data['title'] ?? 'Mitteilung' }}</span>
                    @if (! empty($n->data['body']))<span class="block truncate">{{ $n->data['body'] }}</span>@endif
                    <span class="text-xs">{{ $n->created_at->gt(now()->subMinute()) ? 'gerade eben' : $n->created_at->diffForHumans() }}</span>
                </span>
            </button>
        @empty
            <p class="p-6 text-center text-sm">Keine Mitteilungen.</p>
        @endforelse
    </div>
</div>
