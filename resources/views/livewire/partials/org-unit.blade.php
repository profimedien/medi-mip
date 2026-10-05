@php $hasChildren = $unit->children->isNotEmpty(); @endphp
<li x-data="{ open: {{ $level < 1 ? 'true' : 'false' }} }" @org-all.window="open = $event.detail">
    <div class="flex items-center gap-2">
        <button type="button" @if ($hasChildren) @click="open = ! open" @endif
            @class(['group flex min-w-60 items-center gap-3 rounded-2xl px-4 py-3 text-left ring-1 transition',
                    'bg-gradient-to-r from-medi-blue to-medi-navy text-white ring-medi-blue shadow-md' => $level === 0,
                    'bg-white ring-medi-blue/15 hover:ring-medi-blue/40 hover:shadow-sm' => $level > 0,
                    'cursor-default' => ! $hasChildren])>
            <span @class(['grid size-9 shrink-0 place-items-center rounded-xl', 'bg-white/15' => $level === 0, 'bg-medi-blue/10 text-medi-blue' => $level > 0])>
                <x-dynamic-component :component="'heroicon-o-'.($level === 0 ? 'building-office-2' : ($hasChildren ? 'rectangle-group' : 'user-group'))" class="size-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span @class(['block font-bold', 'text-medi-navy' => $level > 0])>{{ $unit->name }}</span>
                @if ($unit->head_label)
                    <span @class(['block text-xs', 'text-white/80' => $level === 0])>Leitung: {{ $unit->head_label }}</span>
                @endif
            </span>
            @if ($hasChildren)
                <span @class(['flex items-center gap-1 text-xs font-medium', 'text-white/80' => $level === 0, 'text-medi-blue' => $level > 0])>
                    {{ $unit->children->count() }}
                    <x-heroicon-o-chevron-down class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                </span>
            @endif
        </button>
        @if ($unit->head && $unit->head->profile_visible)
            <a href="{{ route('directory.show', $unit->head) }}" wire:navigate class="rounded-full p-2 text-medi-sky hover:bg-medi-bg" title="Profil der Leitung"><x-heroicon-o-user-circle class="size-5" /></a>
        @endif
    </div>
    @if ($hasChildren)
        <ul x-show="open" x-collapse class="ml-6 mt-3 space-y-3 border-l-2 border-medi-blue/15 pl-6">
            @foreach ($unit->children as $child)
                @include('livewire.partials.org-unit', ['unit' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
