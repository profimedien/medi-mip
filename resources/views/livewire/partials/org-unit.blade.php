<li>
    <div @class(['inline-flex min-w-56 flex-col rounded-xl px-4 py-2.5 ring-1',
                 'bg-medi-blue text-white ring-medi-blue' => $level === 0,
                 'bg-white ring-medi-blue/20' => $level > 0])>
        <span @class(['font-bold', 'text-medi-navy' => $level > 0])>{{ $unit->name }}</span>
        @if ($unit->head_label)
            <span @class(['text-xs', 'text-white/80' => $level === 0])>
                @if ($unit->head && $unit->head->profile_visible)
                    <a href="{{ route('directory.show', $unit->head) }}" wire:navigate class="underline">{{ $unit->head_label }}</a>
                @else
                    {{ $unit->head_label }}
                @endif
            </span>
        @endif
    </div>
    @if ($unit->children->isNotEmpty())
        <ul class="mt-3 ml-4 space-y-3 border-l-2 border-medi-blue/15 pl-5">
            @foreach ($unit->children as $child)
                @include('livewire.partials.org-unit', ['unit' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
