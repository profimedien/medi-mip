<x-filament-widgets::widget>
    <div class="mip-grid-2">
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
