{{-- Gemeinsame Fehlerseite im MIP-Look (deutsch, ohne Technikdetails) --}}
<x-layouts.guest :title="$heading">
    <p class="text-sm font-semibold text-medi-gray">Fehler {{ $code }}</p>
    <h1 class="mt-1 text-xl font-bold text-medi-navy">{{ $heading }}</h1>
    <p class="mt-3 text-sm text-medi-gray">{{ $text }}</p>
    <a href="{{ url('/') }}" class="btn mt-6 w-full">Zur Startseite</a>
</x-layouts.guest>
