<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 14mm; }
    body { font-family: DejaVu Sans, sans-serif; color: #485057; font-size: 10.5pt; }
    .sheet { height: 100mm; border: 1px dashed #b4b4b4; border-radius: 4mm; padding: 9mm 10mm; margin-bottom: 6mm; page-break-inside: avoid; position: relative; }
    .break { page-break-after: always; }
    h1 { color: #003d8f; font-size: 15pt; margin: 0 0 1mm; }
    .sub { margin: 0 0 6mm; }
    .qr { position: absolute; right: 10mm; top: 9mm; width: 38mm; }
    .row { margin: 0 0 4mm; }
    .k { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .5pt; color: #6b6b6b; }
    .v { font-size: 15pt; font-weight: bold; color: #0032ba; letter-spacing: 1pt; }
    ol { margin: 4mm 0 0; padding-left: 5mm; width: 120mm; }
    li { margin-bottom: 1.5mm; }
    .foot { position: absolute; bottom: 7mm; left: 10mm; right: 10mm; font-size: 8pt; color: #6b6b6b; }
</style>
</head>
<body>
@foreach ($sheets as $s)
    <div class="sheet{{ $loop->even && ! $loop->last ? ' break' : '' }}">
        <img class="qr" src="{{ $s['qr'] }}" alt="">
        <h1>Ihr Zugang zum MIP · Mitarbeiter-Informationsportal</h1>
        <p class="sub">für {{ $s['user']->name }}{{ $s['user']->location ? ' · '.$s['user']->location->label : '' }}</p>
        <div class="row"><div class="k">Benutzername</div><div class="v">{{ $s['user']->username }}</div></div>
        <div class="row"><div class="k">Startcode (nur für die erste Anmeldung)</div><div class="v">{{ $s['code'] }}</div></div>
        <ol>
            <li>QR-Code mit dem Handy scannen oder {{ $s['url'] }} öffnen.</li>
            <li>Benutzername und Startcode eingeben.</li>
            <li>Eigenes Passwort festlegen. Der Startcode ist danach ungültig.</li>
            <li>Tipp: Im Browser "Zum Home-Bildschirm hinzufügen" wählen, dann ist das Portal wie eine App erreichbar.</li>
        </ol>
        <div class="foot">Bitte diesen Zettel vertraulich behandeln und nach der ersten Anmeldung vernichten.</div>
    </div>
@endforeach
</body>
</html>
