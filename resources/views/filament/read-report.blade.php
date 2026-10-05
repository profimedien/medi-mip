{{-- Inline-Styles, weil das Filament-Theme nur die eigenen Tailwind-Klassen mitbringt --}}
<div style="font-size:14px">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
        @foreach (array_filter(['Zielgruppe' => $total, 'Gelesen' => $read, 'Bestätigt' => $requiresConfirmation ? $confirmed : null], fn ($v) => $v !== null) as $label => $value)
            <div style="background:rgba(0,50,186,.05);border-radius:10px;padding:12px">
                <div style="font-size:12px;opacity:.7">{{ $label }}</div>
                <div style="font-size:22px;font-weight:700">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <div style="max-height:380px;overflow-y:auto;border:1px solid rgba(0,0,0,.1);border-radius:10px">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead><tr style="font-size:12px;opacity:.7"><th style="padding:8px">Name</th><th style="padding:8px">Standort</th><th style="padding:8px">Gelesen</th>@if ($requiresConfirmation)<th style="padding:8px">Bestätigt</th>@endif</tr></thead>
            <tbody>
                @foreach ($rows->sortBy(fn ($r) => $r['confirmed_at'] ? 2 : ($r['read_at'] ? 1 : 0)) as $row)
                    <tr style="border-top:1px solid rgba(0,0,0,.06)">
                        <td style="padding:6px 8px">{{ $row['name'] }}</td>
                        <td style="padding:6px 8px;opacity:.7">{{ $row['location'] }}</td>
                        <td style="padding:6px 8px">{{ $row['read_at']?->format('d.m. H:i') ?? '–' }}</td>
                        @if ($requiresConfirmation)<td style="padding:6px 8px">{{ $row['confirmed_at']?->format('d.m. H:i') ?? '–' }}</td>@endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
