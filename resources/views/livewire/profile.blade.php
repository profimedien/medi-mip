<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex items-center gap-4">
        <x-avatar :user="$user" class="size-16 text-lg" />
        <div>
            <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
            <p class="text-sm">{{ $user->job_title }} · {{ $user->location?->label }} · {{ $user->departments->pluck('name')->join(', ') }}</p>
            <p class="text-xs">Benutzername: {{ $user->username }}</p>
        </div>
    </div>

    <form wire:submit="saveProfile" class="card space-y-4">
        <div>
            <h2 class="font-bold">Mein Profil im Verzeichnis</h2>
            <p class="text-sm">Name, Standort, Abteilung und Funktion stammen aus der Personalverwaltung. Alles andere ist freiwillig.</p>
        </div>
        <label class="flex items-start gap-3 rounded-xl bg-medi-bg p-3 text-sm">
            <input type="checkbox" wire:model="profile.profile_visible" class="mt-0.5 rounded text-medi-blue">
            <span><span class="font-medium text-medi-navy">Profil im Verzeichnis zeigen</span><br>Kolleginnen und Kollegen finden Sie dann im Verzeichnis.</span>
        </label>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="profile.show_birthday" class="rounded text-medi-blue"> Geburtstag im Kalender zeigen (ohne Alter)</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="profile.show_anniversary" class="rounded text-medi-blue"> Dienstjubiläum im Kalender zeigen</label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Dienstliche Durchwahl</label><input type="text" wire:model="profile.phone_extension" class="input"></div>
            <div><label class="label">Dienstliche E-Mail</label><input type="email" wire:model="profile.work_email" class="input">@error('profile.work_email')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label class="label">Qualifikationen</label><textarea wire:model="profile.qualifications" rows="2" class="input"></textarea></div>
            <div class="sm:col-span-2"><label class="label">Über mich</label><textarea wire:model="profile.about" rows="3" class="input"></textarea></div>
            <div class="sm:col-span-2">
                <label class="label">Profilfoto</label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" wire:model="avatar" accept="image/*" class="text-sm">
                    @if ($user->avatarUrl())<button type="button" wire:click="removeAvatar" class="text-sm text-medi-red">Foto entfernen</button>@endif
                </div>
                @error('avatar')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="btn" wire:loading.attr="disabled">Speichern</button>
            @if (session('profile'))<span class="text-sm text-emerald-700">{{ session('profile') }}</span>@endif
        </div>
    </form>

    <div class="card space-y-4">
        <h2 class="font-bold">Benachrichtigungen</h2>
        @if ($user->email)
            <form wire:submit="saveNotifications" class="space-y-2">
                <p class="text-sm">E-Mails an {{ $user->email }}:</p>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="notify.mail_news" class="rounded text-medi-blue"> Wichtige News</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="notify.mail_forms" class="rounded text-medi-blue"> Neue Rückmeldungen und Erinnerungen</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="notify.mail_chat_digest" class="rounded text-medi-blue"> Tägliche Zusammenfassung ungelesener Chat-Nachrichten</label>
                <div class="flex items-center gap-3 pt-1">
                    <button class="btn-ghost">Speichern</button>
                    @if (session('notify'))<span class="text-sm text-emerald-700">{{ session('notify') }}</span>@endif
                </div>
            </form>
        @else
            <p class="text-sm">Für Ihr Konto ist keine E-Mail-Adresse hinterlegt. Neuigkeiten sehen Sie direkt im Portal oder als Mitteilung auf Ihrem Handy (siehe unten).</p>
        @endif

        <div class="border-t border-black/5 pt-4" x-data="{ state: 'loading', async init() { this.state = await window.mipPush.status() } }">
            <p class="text-sm font-medium text-medi-navy">Mitteilungen auf diesem Gerät</p>
            <p class="mb-2 text-xs">Kurze Hinweise auf dem Bildschirm Ihres Handys oder Computers, auch wenn das Portal gerade geschlossen ist.</p>
            <template x-if="state === 'unsupported'">
                <p class="text-sm">Dieses Gerät oder dieser Browser unterstützt keine Push-Mitteilungen. Auf dem iPhone funktionieren Mitteilungen erst, wenn das Portal zum Home-Bildschirm hinzugefügt wurde.</p>
            </template>
            <template x-if="state === 'off' || state === 'denied'">
                <div>
                    <button type="button" class="btn" @click="state = await window.mipPush.enable(@js($vapid))" @disabled(! $vapid)>Mitteilungen aktivieren</button>
                    <p x-show="state === 'denied'" class="mt-2 text-sm text-medi-red">Mitteilungen wurden im Browser abgelehnt. Bitte in den Geräteeinstellungen erlauben.</p>
                </div>
            </template>
            <template x-if="state === 'on'">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1 text-sm text-emerald-700"><x-heroicon-o-check-circle class="size-5" /> aktiv</span>
                    <button type="button" class="text-sm text-medi-red" @click="state = await window.mipPush.disable()">ausschalten</button>
                </div>
            </template>
        </div>
    </div>

    <form wire:submit="changePassword" class="card space-y-4">
        <h2 class="font-bold">Passwort ändern</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="label">Aktuelles Passwort</label><input type="password" wire:model="current_password" class="input" autocomplete="current-password">@error('current_password')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror</div>
            <div><label class="label">Neues Passwort (mindestens 8 Zeichen)</label><input type="password" wire:model="password" class="input" autocomplete="new-password">@error('password')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror</div>
            <div><label class="label">Wiederholen</label><input type="password" wire:model="password_confirmation" class="input" autocomplete="new-password"></div>
        </div>
        <div class="flex items-center gap-3">
            <button class="btn-ghost">Passwort ändern</button>
            @if (session('password'))<span class="text-sm text-emerald-700">{{ session('password') }}</span>@endif
        </div>
    </form>

    <div class="card text-sm">
        <h2 class="mb-1 font-bold">Als App installieren</h2>
        <p><strong class="font-medium">iPhone:</strong> In Safari auf Teilen tippen, dann „Zum Home-Bildschirm“. <strong class="font-medium">Android:</strong> Im Chrome-Menü „App installieren“ wählen.</p>
    </div>
</div>
