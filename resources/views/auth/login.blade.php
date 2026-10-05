<x-layouts.guest title="Anmelden">
    <h1 class="mb-1 text-xl font-bold">Anmelden</h1>
    <p class="mb-5 text-sm">Mit Benutzername oder E-Mail-Adresse. Beim ersten Mal den Startcode von Ihrem Zugangszettel eingeben.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="login" class="label">Benutzername oder E-Mail</label>
            <input id="login" name="login" type="text" class="input" value="{{ old('login', $login) }}" autocomplete="username" autocapitalize="none" required autofocus>
        </div>
        <div>
            <label for="password" class="label">Passwort oder Startcode</label>
            <input id="password" name="password" type="password" class="input" autocomplete="current-password" required>
        </div>
        @error('login')
            <p class="rounded-xl bg-medi-red/5 px-3 py-2 text-sm text-medi-red">{{ $message }}</p>
        @enderror
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" checked class="rounded text-medi-blue"> Angemeldet bleiben
        </label>
        <button class="btn w-full">Anmelden</button>
    </form>
    <p class="mt-5 text-center text-xs text-medi-gray/80">Passwort vergessen? Ihre Standortleitung erstellt Ihnen einen neuen Startcode.</p>
</x-layouts.guest>
