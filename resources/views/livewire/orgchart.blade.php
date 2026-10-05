<div class="space-y-4">
    <h1 class="text-2xl font-bold">Organigramm der medi Gruppe</h1>
    <div class="card overflow-x-auto">
        <ul class="space-y-3">
            @foreach ($roots as $unit)
                @include('livewire.partials.org-unit', ['unit' => $unit, 'level' => 0])
            @endforeach
        </ul>
    </div>
</div>
