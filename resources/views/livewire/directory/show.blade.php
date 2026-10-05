<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('directory.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Verzeichnis</a>
    <div class="card">
        <div class="flex items-center gap-4">
            <x-avatar :user="$user" class="size-20 text-xl" />
            <div>
                <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                <p class="text-sm">{{ $user->job_title }}</p>
            </div>
        </div>
        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs uppercase tracking-wide text-medi-gray/70">Standort</dt><dd class="font-medium text-medi-navy">{{ $user->location?->label }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-medi-gray/70">Abteilung</dt><dd class="font-medium text-medi-navy">{{ $user->departments->pluck('name')->join(', ') }}</dd></div>
            @if ($user->phone_extension)<div><dt class="text-xs uppercase tracking-wide text-medi-gray/70">Durchwahl</dt><dd class="font-medium text-medi-navy">{{ $user->phone_extension }}</dd></div>@endif
            @if ($user->work_email)<div><dt class="text-xs uppercase tracking-wide text-medi-gray/70">E-Mail</dt><dd><a href="mailto:{{ $user->work_email }}" class="font-medium text-medi-sky">{{ $user->work_email }}</a></dd></div>@endif
            @if ($user->qualifications)<div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wide text-medi-gray/70">Qualifikationen</dt><dd>{{ $user->qualifications }}</dd></div>@endif
            @if ($user->about)<div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wide text-medi-gray/70">Über mich</dt><dd>{{ $user->about }}</dd></div>@endif
        </dl>
        @if ($user->id !== auth()->id())
            <a href="{{ route('chat', ['direct' => $user->id]) }}" wire:navigate class="btn mt-5"><x-heroicon-o-chat-bubble-left-right class="size-4" /> Nachricht schreiben</a>
        @endif
    </div>
</div>
