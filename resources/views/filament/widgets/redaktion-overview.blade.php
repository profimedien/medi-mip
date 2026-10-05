<x-filament-widgets::widget>
    <div class="mip-grid-3">
        <div class="mip-card">
            <h3>Nächste Termine</h3>
            @forelse ($events as $e)
                <a href="{{ route('filament.admin.resources.events.index') }}" class="mip-event">
                    <span class="mip-date"><small>{{ $e->starts_at->translatedFormat('M') }}</small>{{ $e->starts_at->format('d') }}</span>
                    <span><b>{{ $e->title }}</b><em>{{ $e->starts_at->translatedFormat('l') }}{{ $e->all_day ? '' : ', '.$e->starts_at->format('H:i').' Uhr' }}{{ $e->place ? ' · '.$e->place : '' }}</em></span>
                </a>
            @empty
                <p class="mip-empty">Keine Termine geplant.</p>
            @endforelse
        </div>
        @foreach (['Rücklauf Rückmeldungen' => $forms, 'Lesebestätigungen' => $posts] as $heading => $rows)
            <div class="mip-card">
                <h3>{{ $heading }}</h3>
                @forelse ($rows as $r)
                    @php $pct = min(100, round($r['done'] / $r['total'] * 100)); @endphp
                    <a href="{{ $r['url'] }}" class="mip-row">
                        <div class="mip-row-head"><b>{{ $r['title'] }}</b><span>{{ $r['done'] }} von {{ $r['total'] }} · {{ $r['meta'] }}</span></div>
                        <div class="mip-bar"><i style="width: {{ $pct }}%"></i></div>
                    </a>
                @empty
                    <p class="mip-empty">Gerade nichts offen.</p>
                @endforelse
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
