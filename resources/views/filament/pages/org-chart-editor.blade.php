<x-filament-panels::page>
    <div class="org-editor" x-data="{
            dragging: null,
            over: null,
            start(e, id) { this.dragging = id; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', id) },
            end() { this.dragging = null; this.over = null },
            drop(parent, before) {
                if (this.dragging === null) return;
                const id = this.dragging; this.end();
                if (id === before || id === parent) return;
                $wire.move(id, parent, before);
            },
        }">
        @if ($roots->isEmpty())
            <p class="mip-empty">Noch keine Bereiche. Legen Sie oben rechts den ersten an.</p>
        @endif
        <ul class="org-list">
            @foreach ($roots as $unit)
                @include('filament.pages.partials.org-node', ['unit' => $unit, 'parent' => null, 'level' => 0])
            @endforeach
        </ul>
        <div class="org-rootzone" :class="over === 'root' && 'is-over'"
             @dragover.prevent="over = 'root'" @dragleave="over = null" @drop.prevent="drop(null, null)" x-show="dragging !== null">
            Hier ablegen: auf oberste Ebene verschieben
        </div>
    </div>
</x-filament-panels::page>
