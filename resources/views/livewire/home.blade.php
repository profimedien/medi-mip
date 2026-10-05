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
                @include('livewire.news.partials.card', ['post' => $post, 'featured' => $loop->first])
            @empty
                <p class="card text-sm">Noch keine Neuigkeiten.</p>
            @endforelse
        </section>

        <div class="space-y-6">
            <section class="card">
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="font-bold">Nächste Termine</h2>
                    <a href="{{ route('calendar') }}" wire:navigate class="text-sm text-medi-sky">Kalender</a>
                </div>
                <div class="space-y-2">
                    @forelse ($events as $event)
                        <a href="{{ route('calendar', ['month' => $event->starts_at->format('Y-m')]) }}" wire:navigate class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-medi-bg">
                            <span class="grid w-14 shrink-0 place-items-center rounded-xl bg-medi-blue py-1.5 text-white">
                                <span class="text-[10px] font-medium uppercase tracking-wide opacity-80">{{ $event->starts_at->translatedFormat('M') }}</span>
                                <span class="text-xl font-bold leading-none">{{ $event->starts_at->format('d') }}</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-medi-navy">{{ $event->title }}</span>
                                <span class="block truncate text-xs">{{ $event->starts_at->translatedFormat('l') }}{{ $event->all_day ? '' : ', '.$event->starts_at->format('H:i').' Uhr' }}{{ $event->place ? ' · '.$event->place : '' }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="text-sm">Aktuell sind keine Termine geplant.</p>
                    @endforelse
                </div>
                @if ($today->isNotEmpty())
                    <div class="mt-4 border-t border-black/5 pt-3">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-medi-gray/70">Heute</p>
                        @foreach ($today as $t)
                            <p class="flex items-center gap-2 py-0.5 text-sm">
                                <x-dynamic-component :component="'heroicon-o-'.($t['type'] === 'birthday' ? 'cake' : 'trophy')" @class(['size-4 shrink-0', 'text-orange-500' => $t['type'] === 'birthday', 'text-emerald-600' => $t['type'] !== 'birthday']) />
                                {{ $t['title'] }}
                            </p>
                        @endforeach
                    </div>
                @endif
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
