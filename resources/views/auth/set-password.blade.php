<x-layouts.guest title="Passwort festlegen">
    <h1 class="mb-1 text-xl font-bold">Willkommen, {{ auth()->user()->first_name }}!</h1>
    <p class="mb-5 text-sm">Bitte legen Sie jetzt Ihr persönliches Passwort fest. Ihr Benutzername lautet <strong class="font-medium text-medi-navy">{{ auth()->user()->username }}</strong>.</p>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="label">Neues Passwort (mindestens 8 Zeichen)</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required autofocus>
        </div>
        <div>
            <label for="password_confirmation" class="label">Passwort wiederholen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
        </div>
        @error('password')
            <p class="rounded-xl bg-medi-red/5 px-3 py-2 text-sm text-medi-red">{{ $message }}</p>
        @enderror
        <button class="btn w-full">Passwort speichern</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button class="text-xs text-medi-gray/80 underline">Abbrechen und abmelden</button>
    </form>
</x-layouts.guest>
