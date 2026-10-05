<li class="org-node" wire:key="org-{{ $unit->id }}">
    <div class="org-gap" :class="over === 'b{{ $unit->id }}' && 'is-over'"
         @dragover.prevent="dragging !== {{ $unit->id }} && (over = 'b{{ $unit->id }}')" @dragleave="over = null"
         @drop.prevent="drop({{ $parent ?? 'null' }}, {{ $unit->id }})"></div>
    <div class="org-card {{ $level === 0 ? 'is-root' : '' }}" draggable="true"
         :class="{ 'is-dragging': dragging === {{ $unit->id }}, 'is-over': over === 'i{{ $unit->id }}' }"
         @dragstart="start($event, {{ $unit->id }})" @dragend="end()"
         @dragover.prevent="dragging !== {{ $unit->id }} && (over = 'i{{ $unit->id }}')" @dragleave="over = null"
         @drop.prevent="drop({{ $unit->id }}, null)">
        <span class="org-handle" title="Ziehen zum Verschieben">
            <x-heroicon-o-arrows-up-down />
        </span>
        <span class="org-text">
            <b>{{ $unit->name }}</b>
            <em>{{ $unit->head_label ? 'Leitung: '.$unit->head_label : 'ohne Leitung' }}{{ $unit->children->isNotEmpty() ? ' · '.$unit->children->count().' Untereinheit(en)' : '' }}</em>
        </span>
        <span class="org-actions">
            <button type="button" wire:click="mountAction('add', { parent: {{ $unit->id }} })" title="Unterbereich anlegen"><x-heroicon-o-plus /></button>
            <button type="button" wire:click="mountAction('edit', { id: {{ $unit->id }} })" title="Bearbeiten"><x-heroicon-o-pencil-square /></button>
            <button type="button" wire:click="mountAction('delete', { id: {{ $unit->id }} })" title="Löschen" class="is-danger"><x-heroicon-o-trash /></button>
        </span>
    </div>
    @if ($unit->children->isNotEmpty())
        <ul class="org-list org-children">
            @foreach ($unit->children as $child)
                @include('filament.pages.partials.org-node', ['unit' => $child, 'parent' => $unit->id, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
