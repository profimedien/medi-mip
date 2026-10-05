@php
    $styles = [
        'event' => ['bg-medi-blue', 'calendar-days', 'Termin'],
        'anniversary' => ['bg-emerald-600', 'trophy', 'Jubiläum'],
        'birthday' => ['bg-orange-500', 'cake', 'Geburtstag'],
    ][$entry['type']];
@endphp
<div class="flex gap-3 py-2">
    <div class="w-11 shrink-0 text-center">
        <div class="text-lg font-bold leading-none text-medi-navy">{{ $entry['date']->format('d') }}</div>
        <div class="text-[11px] uppercase">{{ $entry['date']->translatedFormat('M') }}</div>
    </div>
    <div class="min-w-0 border-l-4 pl-3 {{ str_replace('bg-', 'border-', $styles[0]) }}">
        <div class="flex items-center gap-1.5 text-sm font-medium text-medi-navy">
            <x-dynamic-component :component="'heroicon-o-'.$styles[1]" class="size-4 shrink-0" />
            <span class="truncate">{{ $entry['title'] }}</span>
        </div>
        <div class="text-xs">
            {{ $entry['date']->translatedFormat('l') }}{{ ! empty($entry['time']) ? ', '.$entry['time'].' Uhr' : '' }}{{ ! empty($entry['place']) ? ' · '.$entry['place'] : '' }}
        </div>
        @if (empty($compact) && ! empty($entry['description']))
            <p class="mt-1 text-sm">{{ $entry['description'] }}</p>
        @endif
    </div>
</div>
