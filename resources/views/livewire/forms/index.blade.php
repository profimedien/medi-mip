<div class="space-y-6">
    <h1 class="text-2xl font-bold">Rückmeldungen</h1>

    <section class="space-y-3">
        <h2 class="font-bold">Offen</h2>
        @forelse ($open as $form)
            <a href="{{ route('forms.show', $form) }}" wire:navigate class="card flex items-center gap-4 transition hover:ring-medi-sky/40">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-medi-red/10 text-medi-red"><x-heroicon-o-clipboard-document-check class="size-5" /></span>
                <span class="min-w-0">
                    <span class="block font-bold text-medi-navy">{{ $form->title }}</span>
                    <span class="text-xs">{{ $form->deadline ? 'Antwort bis '.$form->deadline->translatedFormat('j. F, H:i').' Uhr' : 'ohne Frist' }}</span>
                </span>
                <x-heroicon-o-chevron-right class="ml-auto size-5 shrink-0" />
            </a>
        @empty
            <p class="card text-sm">Alles erledigt. Es gibt keine offenen Rückmeldungen.</p>
        @endforelse
    </section>

    @if ($done->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-bold">Erledigt oder abgelaufen</h2>
            @foreach ($done as $form)
                @php $r = $form->responses->first(); @endphp
                <a href="{{ route('forms.show', $form) }}" wire:navigate class="card flex items-center gap-4 opacity-80 transition hover:opacity-100">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><x-heroicon-o-check-circle class="size-5" /></span>
                    <span class="min-w-0">
                        <span class="block font-medium text-medi-navy">{{ $form->title }}</span>
                        <span class="text-xs">{{ $r ? 'Ihre Antwort: '.(\App\Models\Form::CHOICES[$r->choice] ?? 'abgegeben').' am '.$r->updated_at->format('d.m.Y') : 'Frist abgelaufen' }}</span>
                    </span>
                </a>
            @endforeach
        </section>
    @endif
</div>
