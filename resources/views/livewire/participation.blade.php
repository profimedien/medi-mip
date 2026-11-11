<div class="space-y-4">
    <h1 class="text-2xl font-bold">Mitmachen</h1>
    <div class="flex gap-1 overflow-x-auto rounded-xl bg-white p-1 ring-1 ring-black/5">
        @foreach (\App\Livewire\Participation::TABS as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')" @class(['shrink-0 rounded-lg px-3 py-2 text-sm font-medium', 'bg-medi-blue text-white' => $tab === $key, 'hover:bg-medi-bg' => $tab !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($done)
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ $done }}</p>
    @endif

    <div class="card max-w-2xl">
        @if ($tab === 'vorschlag')
            <p class="mb-4 text-sm">Was könnten wir besser machen? Ihre Idee geht direkt an die zuständige Stelle.</p>
            <form wire:submit="sendSuggestion" class="space-y-4">
                <div>
                    <label class="label">Thema</label>
                    <select wire:model="suggestion.topic" class="input">
                        <option value="">Bitte wählen</option>
                        @foreach ($topics as $t)<option>{{ $t }}</option>@endforeach
                    </select>
                    @error('suggestion.topic')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Ihre Idee</label>
                    <textarea wire:model="suggestion.body" rows="5" class="input"></textarea>
                    @error('suggestion.body')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                </div>
                <button class="btn">Idee senden</button>
            </form>
        @elseif ($tab === 'hilfe')
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-medi-blue/10 text-medi-blue"><x-heroicon-o-hand-raised class="size-5" /></span>
                <div class="min-w-0 text-sm">
                    <h2 class="mb-1 font-bold text-medi-navy">medi hilft</h2>
                    @if (filled($helpText))
                        <div class="whitespace-pre-line break-words">{!! preg_replace('~https?://[^\s<]+~', '<a href="$0" target="_blank" rel="noopener" class="text-medi-sky underline">$0</a>', e($helpText)) !!}</div>
                    @else
                        <p>Hier finden Sie bald Informationen und Anträge rund um die Unterstützung durch medi.</p>
                    @endif
                </div>
            </div>
        @elseif ($tab === 'empfehlung')
            <div class="prose-medi mb-4 rounded-xl bg-medi-bg p-4 text-sm">{!! nl2br(e($referralText)) !!}</div>
            <form wire:submit="sendReferral" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Name der Person</label><input type="text" wire:model="referral.candidate_name" class="input">
                    @error('referral.candidate_name')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Telefon oder E-Mail</label><input type="text" wire:model="referral.candidate_contact" class="input">
                    @error('referral.candidate_contact')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2"><label class="label">Für welche Stelle?</label><input type="text" wire:model="referral.position" class="input" placeholder="z. B. Physiotherapie Fürth"></div>
                <div class="sm:col-span-2"><label class="label">Anmerkung</label><textarea wire:model="referral.note" rows="3" class="input"></textarea></div>
                <p class="text-xs sm:col-span-2">Bitte nur mit Einverständnis der empfohlenen Person.</p>
                <div class="sm:col-span-2"><button class="btn">Empfehlung senden</button></div>
            </form>
        @endif
    </div>
</div>
