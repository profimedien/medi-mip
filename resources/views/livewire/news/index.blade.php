<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">News</h1>
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="News durchsuchen" class="input w-full sm:w-64">
    </div>

    @forelse ($posts as $post)
        @include('livewire.news.partials.card', ['post' => $post])
    @empty
        <p class="card text-sm">Keine News gefunden.</p>
    @endforelse

    @if ($total > $posts->count())
        <div class="text-center"><button wire:click="more" class="btn-ghost">Weitere laden</button></div>
    @endif
</div>
