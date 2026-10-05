<div class="space-y-4" x-data="{ all: null }">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">Organigramm der medi Gruppe</h1>
        <button @click="all = true; $dispatch('org-all', true)" class="btn-ghost py-2">Alle aufklappen</button>
        <button @click="all = false; $dispatch('org-all', false)" class="btn-ghost py-2">Alle zuklappen</button>
    </div>
    <p class="text-sm">Tippen Sie auf einen Bereich, um die Ebenen darunter zu sehen.</p>
    <div class="card overflow-x-auto">
        <ul class="space-y-3">
            @foreach ($roots as $unit)
                @include('livewire.partials.org-unit', ['unit' => $unit, 'level' => 0])
            @endforeach
        </ul>
    </div>
</div>
