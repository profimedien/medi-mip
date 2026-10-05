<div style="font-size:14px">
    <p style="margin-bottom:8px">{{ $people->count() }} {{ $people->count() === 1 ? 'Person hat' : 'Personen haben' }} noch nicht geantwortet.</p>
    <ul style="max-height:380px;overflow-y:auto;border:1px solid rgba(0,0,0,.1);border-radius:10px">
        @foreach ($people as $p)
            <li style="padding:6px 10px;border-top:1px solid rgba(0,0,0,.05)">{{ $p->name }} <span style="opacity:.6">· {{ $p->location?->short_name }}{{ $p->email ? '' : ' · ohne E-Mail' }}</span></li>
        @endforeach
    </ul>
</div>
