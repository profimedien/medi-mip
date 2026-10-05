<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Hallo {{ $user->first_name }}</h1>
        <p class="text-sm">{{ now()->translatedFormat('l, j. F Y') }}{{ $user->location ? ' · '.$user->location->label : '' }}</p>
    </div>

    @if ($openForms->isNotEmpty())
        <section class="card border-l-4 border-medi-red">
            <h2 class="mb-3 flex items-center gap-2 font-bold"><x-heroicon-o-clipboard-document-check class="size-5" /> Ihre Rückmeldung fehlt noch</h2>
            <ul class="divide-y divide-black/5">
                @foreach ($openForms as $form)
                    <li>
                        <a href="{{ route('forms.show', $form) }}" wire:navigate class="flex items-center justify-between gap-3 py-2.5 text-sm hover:text-medi-blue">
                            <span class="font-medium text-medi-navy">{{ $form->title }}</span>
                            @if ($form->deadline)
                                <span class="shrink-0 text-xs">bis {{ $form->deadline->format('d.m.') }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="space-y-3 xl:col-span-2">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-bold">Neuigkeiten</h2>
                <a href="{{ route('news.index') }}" wire:navigate class="text-sm text-medi-sky">Alle News</a>
            </div>
            @forelse ($posts as $post)
                @include('livewire.news.partials.card', ['post' => $post])
            @empty
                <p class="card text-sm">Noch keine Neuigkeiten.</p>
            @endforelse
        </section>

        <div class="space-y-6">
            <section class="card">
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="font-bold">Demnächst</h2>
                    <a href="{{ route('calendar') }}" wire:navigate class="text-sm text-medi-sky">Kalender</a>
                </div>
                @forelse ($upcoming as $entry)
                    @include('livewire.partials.calendar-entry', ['entry' => $entry, 'compact' => true])
                @empty
                    <p class="text-sm">In den nächsten zwei Wochen steht nichts an.</p>
                @endforelse
            </section>

            <section class="card">
                <h2 class="mb-3 font-bold">Schnellzugriff</h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($links as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="noopener" class="rounded-xl bg-medi-bg p-3 text-sm hover:bg-medi-blue/5">
                            <span class="flex items-center gap-1.5 font-medium text-medi-navy"><x-heroicon-o-link class="size-4" /> {{ $link->title }}</span>
                            @if ($link->description)<span class="mt-0.5 block text-xs">{{ $link->description }}</span>@endif
                        </a>
                    @endforeach
                    <a href="{{ route('onboarding') }}" wire:navigate class="rounded-xl bg-medi-bg p-3 text-sm hover:bg-medi-blue/5">
                        <span class="flex items-center gap-1.5 font-medium text-medi-navy"><x-heroicon-o-academic-cap class="size-4" /> Onboarding</span>
                        <span class="mt-0.5 block text-xs">Urlaub, Krankmeldung, Benefits</span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
