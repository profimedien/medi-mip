<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">News</h1>
        @if ($unread)
            <button wire:click="markAllRead" class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-check-circle class="size-4" /> Alle {{ $unread }} als gelesen markieren</button>
        @endif
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="News durchsuchen" class="input w-full sm:w-64">
    </div>

    @forelse ($posts as $post)
        @include('livewire.news.partials.card', ['post' => $post, 'featured' => $loop->first && ! $search])
    @empty
        <p class="card text-sm">Keine News gefunden.</p>
    @endforelse

    @if ($total > $posts->count())
        <div class="text-center"><button wire:click="more" class="btn-ghost">Weitere laden</button></div>
    @endif
</div>
