<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('forms.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-medi-sky"><x-heroicon-o-arrow-left class="size-4" /> Alle Rückmeldungen</a>

    <div class="card space-y-4">
        @if ($image)
            <img src="{{ route('media.show', $image) }}" alt="" class="-mx-5 -mt-5 w-[calc(100%+2.5rem)] max-w-none rounded-t-2xl object-cover">
        @endif
        <div>
            <h1 class="text-2xl font-bold">{{ $form->title }}</h1>
            @if ($form->deadline)
                <p class="text-sm {{ $form->isOpen() ? '' : 'text-medi-red' }}">{{ $form->isOpen() ? 'Antwort bis' : 'Frist abgelaufen am' }} {{ $form->deadline->translatedFormat('j. F Y, H:i') }} Uhr</p>
            @endif
        </div>
        <div class="prose-medi text-sm">{!! $form->description !!}</div>

        @if ($attachments->isNotEmpty())
            <ul class="space-y-1.5">
                @foreach ($attachments as $file)
                    <li><a href="{{ route('media.show', $file) }}" target="_blank" class="flex items-center gap-2 text-sm text-medi-sky hover:underline"><x-heroicon-o-paper-clip class="size-4" /> {{ $file->file_name }}</a></li>
                @endforeach
            </ul>
        @endif

        @if (session('status'))
            <p class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        @if ($response && ! $editing)
            <div class="rounded-xl bg-medi-bg p-4 text-sm">
                <p class="mb-2 font-medium text-medi-navy">Ihre Antwort vom {{ $response->updated_at->format('d.m.Y, H:i') }} Uhr</p>
                @if ($response->choice)<p>{{ $response->choice }}</p>@endif
                @foreach ($form->fields as $field)
                    @php $a = $response->answers[$field->id] ?? null; @endphp
                    @if ($a !== null && $a !== '' && $a !== [])
                        <p><span class="text-medi-gray/80">{{ $field->label }}:</span> {{ is_array($a) ? implode(', ', $a) : ($field->type === 'boolean' ? ($a ? 'Ja' : 'Nein') : $a) }}</p>
                    @endif
                @endforeach
                @if ($response->signature_path)<p class="mt-1 flex items-center gap-1 text-emerald-700"><x-heroicon-o-check-circle class="size-4" /> unterschrieben</p>@endif
                @if ($form->isOpen() && ! $form->requires_signature)
                    <button wire:click="$set('editing', true)" class="btn-ghost mt-3">Antwort ändern</button>
                @endif
            </div>
        @elseif ($form->isOpen())
            <form wire:submit="submit" class="space-y-5 border-t border-black/5 pt-4">
                @if ($form->choices)
                    <div>
                        <span class="label">Ihre Antwort</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($form->choiceOptions() as $label)
                                <label @class(['cursor-pointer rounded-xl px-4 py-2.5 text-sm font-medium ring-1 transition',
                                               'bg-medi-blue text-white ring-medi-blue' => $choice === $label,
                                               'bg-white ring-black/10' => $choice !== $label])>
                                    <input type="radio" wire:model.live="choice" value="{{ $label }}" class="sr-only"> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('choice')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if ($form->fields->isNotEmpty() && ! \App\Models\Form::isDecline($choice))
                    <div class="space-y-4">
                        @foreach ($form->fields as $field)
                            <div>
                                <label class="label">{{ $field->label }}@if ($field->required) <span class="text-medi-red">*</span>@endif</label>
                                @switch($field->type)
                                    @case('boolean')
                                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="answers.{{ $field->id }}" class="rounded text-medi-blue"> Ja</label>
                                        @break
                                    @case('select')
                                        <select wire:model="answers.{{ $field->id }}" class="input">
                                            <option value="">Bitte wählen</option>
                                            @foreach ($field->options ?? [] as $opt)<option>{{ $opt }}</option>@endforeach
                                        </select>
                                        @break
                                    @case('multiselect')
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($field->options ?? [] as $opt)
                                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="answers.{{ $field->id }}" value="{{ $opt }}" class="rounded text-medi-blue"> {{ $opt }}</label>
                                            @endforeach
                                        </div>
                                        @break
                                    @case('number')
                                        <input type="number" min="0" wire:model="answers.{{ $field->id }}" class="input w-32">
                                        @break
                                    @default
                                        <textarea wire:model="answers.{{ $field->id }}" rows="2" class="input"></textarea>
                                @endswitch
                                @error('answers.'.$field->id)<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($form->requires_signature)
                    <div x-data="signaturePad('signature')" wire:ignore.self>
                        <span class="label">Unterschrift</span>
                        <div wire:ignore>
                            <canvas x-ref="canvas" class="h-40 w-full touch-none rounded-xl bg-white ring-1 ring-black/10"
                                    @mousedown="start" @mousemove="move" @mouseup="end" @mouseleave="end"
                                    @touchstart.prevent="start" @touchmove.prevent="move" @touchend="end"></canvas>
                        </div>
                        <div class="mt-1 flex justify-between text-xs">
                            <span>Mit dem Finger oder der Maus unterschreiben.</span>
                            <button type="button" @click="clear" class="text-medi-sky">Löschen</button>
                        </div>
                        @error('signature')<p class="mt-1 text-sm text-medi-red">{{ $message }}</p>@enderror
                    </div>
                @endif

                <button class="btn w-full sm:w-auto" wire:loading.attr="disabled">Rückmeldung senden</button>
            </form>
        @else
            <p class="rounded-xl bg-medi-bg p-4 text-sm">Die Frist ist abgelaufen. Eine Antwort ist nicht mehr möglich.</p>
        @endif
    </div>
</div>
