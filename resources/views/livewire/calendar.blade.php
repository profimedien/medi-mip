@php
    $colors = ['event' => 'bg-medi-blue', 'anniversary' => 'bg-emerald-600', 'birthday' => 'bg-orange-500'];
    $chips = ['event' => 'bg-medi-blue/10 text-medi-blue', 'anniversary' => 'bg-emerald-50 text-emerald-700', 'birthday' => 'bg-orange-50 text-orange-700'];
    $today = now()->toDateString();
@endphp
<div class="space-y-4">
    {{-- Kopf mit Monatsnavigation --}}
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">{{ $start->translatedFormat('F Y') }}</h1>
        <button wire:click="today" class="btn-ghost py-2">Heute</button>
        <div class="flex items-center rounded-xl bg-white p-1 ring-1 ring-black/5">
            <button wire:click="shift(-1)" class="rounded-lg p-1.5 hover:bg-medi-bg" aria-label="Vormonat"><x-heroicon-o-chevron-left class="size-5" /></button>
            <button wire:click="shift(1)" class="rounded-lg p-1.5 hover:bg-medi-bg" aria-label="Nächster Monat"><x-heroicon-o-chevron-right class="size-5" /></button>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="event" class="rounded text-medi-blue"> <span class="size-2.5 rounded-full bg-medi-blue"></span> Termine</label>
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="anniversary" class="rounded text-emerald-600"> <span class="size-2.5 rounded-full bg-emerald-600"></span> Jubiläen</label>
        <label class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-black/5"><input type="checkbox" wire:model.live="types" value="birthday" class="rounded text-orange-500"> <span class="size-2.5 rounded-full bg-orange-500"></span> Geburtstage</label>
        <select wire:model.live="location" class="input ml-auto w-auto py-1.5">
            <option value="mine">Mein Standort</option>
            <option value="all">Alle Standorte</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Monatsraster --}}
    <div class="card overflow-hidden p-0">
        <div class="grid grid-cols-7 border-b border-black/5 bg-medi-bg/60 text-center text-xs font-medium uppercase tracking-wide text-medi-gray/80">
            @foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $wd)
                <div class="py-2">{{ $wd }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $d)
                    @php
                        $key = $d->toDateString();
                        $list = $entries[$key] ?? collect();
                        $inMonth = $d->month === $start->month;
                        $isSelected = $selected && $selected->toDateString() === $key;
                    @endphp
                    <button type="button" wire:click="select('{{ $key }}')" wire:key="d{{ $key }}"
                        @class(['group relative flex min-h-16 flex-col items-stretch gap-1 border-b border-r border-black/5 p-1.5 text-left transition sm:min-h-28 sm:p-2',
                                'bg-white hover:bg-medi-bg/60' => $inMonth && ! $isSelected,
                                'bg-medi-bg/40 text-medi-gray/40' => ! $inMonth,
                                'bg-medi-blue/5 ring-2 ring-inset ring-medi-blue' => $isSelected,
                                'border-r-0' => $d->dayOfWeekIso === 7])>
                        <span @class(['grid size-7 place-items-center self-center rounded-full text-sm font-medium sm:self-start',
                                      'bg-medi-red text-white' => $key === $today,
                                      'text-medi-navy' => $key !== $today && $inMonth,
                                      'opacity-80' => $d->isWeekend() && $key !== $today])>{{ $d->day }}</span>

                        {{-- Handy: Punkte --}}
                        @if ($list->isNotEmpty())
                            <span class="flex justify-center gap-0.5 sm:hidden">
                                @foreach ($list->pluck('type')->unique() as $t)
                                    <span class="size-1.5 rounded-full {{ $colors[$t] }}"></span>
                                @endforeach
                            </span>
                        @endif

                        {{-- Desktop: Einträge als Chips --}}
                        <span class="hidden space-y-0.5 sm:block">
                            @foreach ($list->take(3) as $e)
                                <span class="flex items-center gap-1 truncate rounded-md px-1.5 py-0.5 text-[11px] font-medium {{ $chips[$e['type']] }}">
                                    @if ($e['type'] === 'birthday')<x-heroicon-s-cake class="size-3 shrink-0" />@elseif ($e['type'] === 'anniversary')<x-heroicon-s-trophy class="size-3 shrink-0" />@endif
                                    <span class="truncate">@if (! empty($e['time'])){{ $e['time'] }} @endif{{ $e['type'] === 'event' ? $e['title'] : \Illuminate\Support\Str::after($e['title'], ': ') }}</span>
                                </span>
                            @endforeach
                            @if ($list->count() > 3)
                                <span class="block px-1.5 text-[11px] font-medium text-medi-gray">+ {{ $list->count() - 3 }} weitere</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Einträge des gewählten Tages bzw. des Monats --}}
    <div class="card">
        @if ($selected)
            <div class="mb-1 flex items-center justify-between">
                <h2 class="font-bold">{{ $selected->translatedFormat('l, j. F') }}</h2>
                <button wire:click="$set('day', null)" class="text-sm text-medi-sky">ganzer Monat</button>
            </div>
            @forelse ($selectedEntries as $entry)
                @include('livewire.partials.calendar-entry', ['entry' => $entry])
            @empty
                <p class="py-3 text-sm">An diesem Tag steht nichts an.</p>
            @endforelse
        @else
            <h2 class="mb-1 font-bold">Alles im {{ $start->translatedFormat('F') }}</h2>
            <div class="divide-y divide-black/5">
                @forelse ($monthEntries as $entry)
                    @include('livewire.partials.calendar-entry', ['entry' => $entry])
                @empty
                    <p class="py-3 text-sm">In diesem Monat gibt es keine Einträge.</p>
                @endforelse
            </div>
        @endif
    </div>
    <p class="text-xs">Manche Termine betreffen nur bestimmte Standorte oder Abteilungen. Geburtstage und Jubiläen zeigen wir für den gewählten Standort.</p>
</div>
