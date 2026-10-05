<div class="flex h-[calc(100dvh-11rem)] gap-4 lg:h-[calc(100dvh-7.5rem)]">
    {{-- Gruppenliste --}}
    <aside @class(['card flex w-full flex-col p-0 md:w-80 md:shrink-0', 'hidden md:flex' => $group])>
        <div class="flex items-center justify-between border-b border-black/5 p-4">
            <h1 class="text-xl font-bold">Medigram</h1>
            <div class="flex items-center gap-1">
                <button wire:click="openNewChat" class="rounded-lg p-1.5 text-medi-blue hover:bg-medi-bg" title="Neuer Direktchat" aria-label="Neuer Direktchat"><x-heroicon-o-pencil-square class="size-6" /></button>
                <button wire:click="openNewGroup" class="rounded-lg p-1.5 text-medi-blue hover:bg-medi-bg" title="Neue Gruppe" aria-label="Neue Gruppe"><x-heroicon-o-user-group class="size-6" /></button>
            </div>
        </div>
        @if ($newGroup)
            <form wire:submit="createGroup" class="space-y-3 border-b border-black/5 bg-medi-bg/50 p-3">
                <p class="text-sm font-bold text-medi-navy">Neue Gruppe</p>
                <div>
                    <input type="text" wire:model="groupName" class="input" placeholder="Name, z. B. Lauftreff Fürth">
                    @error('groupName')<p class="mt-1 text-xs text-medi-red">{{ $message }}</p>@enderror
                </div>
                @if ($selectedMembers->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($selectedMembers as $m)
                            <span class="inline-flex items-center gap-1 rounded-full bg-medi-blue/10 py-0.5 pl-2.5 pr-1 text-xs font-medium text-medi-blue">
                                {{ $m->name }}
                                <button type="button" wire:click="removeMember({{ $m->id }})" class="rounded-full p-0.5 hover:bg-medi-blue/20" aria-label="Entfernen"><x-heroicon-o-x-mark class="size-3" /></button>
                            </span>
                        @endforeach
                    </div>
                @endif
                <div>
                    <input type="search" wire:model.live.debounce.300ms="memberSearch" class="input" placeholder="Personen hinzufügen">
                    @foreach ($memberResults as $person)
                        <button type="button" wire:click="addMember({{ $person->id }})" class="mt-1 flex w-full items-center gap-2 rounded-lg p-2 text-left text-sm hover:bg-white">
                            <x-avatar :user="$person" class="size-7 text-[10px]" /> {{ $person->name }} <span class="text-xs">{{ $person->location?->label }}</span>
                            <x-heroicon-o-plus class="ml-auto size-4 text-medi-blue" />
                        </button>
                    @endforeach
                    @error('groupMembers')<p class="mt-1 text-xs text-medi-red">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <button class="btn flex-1">Gruppe erstellen</button>
                    <button type="button" wire:click="$set('newGroup', false)" class="btn-ghost">Abbrechen</button>
                </div>
            </form>
        @endif
        @if ($newChat)
            <div class="border-b border-black/5 p-3">
                <input type="search" wire:model.live.debounce.300ms="personSearch" class="input" placeholder="Person suchen" autofocus>
                @foreach ($people as $person)
                    <a href="{{ route('chat', ['direct' => $person->id]) }}" wire:navigate class="mt-1 flex items-center gap-2 rounded-lg p-2 text-sm hover:bg-medi-bg">
                        <x-avatar :user="$person" class="size-7 text-[10px]" /> {{ $person->name }} <span class="text-xs">{{ $person->location?->label }}</span>
                    </a>
                @endforeach
            </div>
        @endif
        <ul class="flex-1 overflow-y-auto" wire:poll.15s>
            @foreach ($groups as $g)
                @php $unread = $g->unreadCountFor(auth()->user()); @endphp
                <li>
                    <a href="{{ route('chat', $g) }}" wire:navigate @class(['flex items-center gap-3 px-4 py-3 text-sm', 'bg-medi-blue/5' => $group?->id === $g->id, 'hover:bg-medi-bg' => $group?->id !== $g->id])>
                        <span @class(['grid size-10 shrink-0 place-items-center rounded-full',
                            'bg-medi-red/10 text-medi-red' => $g->type === 'announcement',
                            'bg-medi-blue/10 text-medi-blue' => $g->type !== 'announcement'])>
                            <x-dynamic-component :component="'heroicon-o-'.match($g->type) { 'announcement' => 'megaphone', 'location' => 'map-pin', 'department' => 'user-group', 'direct' => 'user', default => 'chat-bubble-left-right' }" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-medi-navy">{{ $g->titleFor(auth()->user()) }}</span>
                            <span class="block text-xs">{{ \App\Models\ChatGroup::TYPES[$g->type] }}{{ $g->last_message_at ? ' · '.$g->last_message_at->diffForHumans(short: true) : '' }}</span>
                        </span>
                        @if ($unread)<span class="rounded-full bg-medi-red px-2 text-xs font-bold text-white">{{ $unread }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    {{-- Unterhaltung --}}
    <section @class(['card min-w-0 flex-1 flex-col p-0', 'flex' => $group, 'hidden md:flex' => ! $group])>
        @if ($group)
            <div class="flex items-center gap-3 border-b border-black/5 p-4">
                <a href="{{ route('chat') }}" wire:navigate class="md:hidden" aria-label="Zurück"><x-heroicon-o-arrow-left class="size-5" /></a>
                <div class="min-w-0 flex-1">
                    @if ($renaming)
                        <form wire:submit="rename" class="flex items-center gap-2">
                            <input type="text" wire:model="newName" class="input py-1.5" autofocus>
                            <button class="btn py-1.5">Speichern</button>
                            <button type="button" wire:click="$set('renaming', false)" class="text-sm text-medi-gray">Abbrechen</button>
                        </form>
                        @error('newName')<p class="mt-1 text-xs text-medi-red">{{ $message }}</p>@enderror
                    @else
                        <h2 class="flex items-center gap-2 truncate font-bold">
                            {{ $group->titleFor(auth()->user()) }}
                            @if ($canRename)
                                <button wire:click="startRename" class="rounded-lg p-1 text-medi-gray hover:bg-medi-bg hover:text-medi-blue" title="Gruppe umbenennen"><x-heroicon-o-pencil class="size-4" /></button>
                            @endif
                        </h2>
                    @endif
                    <p class="text-xs">{{ $group->type === 'direct' ? 'Direktchat' : $group->members->count().' Mitglieder' }}{{ $group->read_only ? ' · nur Moderation schreibt' : '' }}</p>
                </div>
                @if ($group->type === 'custom')
                    <button wire:click="leaveGroup" wire:confirm="Gruppe wirklich verlassen?" class="ml-auto text-xs text-medi-red">Verlassen</button>
                @endif
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto p-4" wire:poll.5s x-data x-init="$el.scrollTop = $el.scrollHeight"
                 @chat-sent.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                @forelse ($messages as $msg)
                    @php $mine = $msg->user_id === auth()->id(); @endphp
                    <div @class(['group flex gap-2', 'flex-row-reverse' => $mine]) wire:key="m{{ $msg->id }}">
                        @unless ($mine)<x-avatar :user="$msg->user ?? new \App\Models\User(['first_name' => '?', 'last_name' => ''])" class="size-8 text-[10px]" />@endunless
                        <div @class(['max-w-[80%] rounded-2xl px-3.5 py-2 text-sm', 'bg-medi-blue text-white' => $mine, 'bg-medi-bg' => ! $mine])>
                            @unless ($mine)<div class="mb-0.5 text-xs font-medium text-medi-navy">{{ $msg->user?->name }}</div>@endunless
                            @if ($msg->deleted_at)
                                <em class="opacity-70">Nachricht gelöscht</em>
                            @else
                                @if ($msg->body)<div class="whitespace-pre-line break-words">{{ $msg->body }}</div>@endif
                                @foreach ($msg->getMedia('files') as $file)
                                    @if (str_starts_with($file->mime_type, 'image/'))
                                        <a href="{{ route('media.show', $file) }}" target="_blank"><img src="{{ route('media.show', [$file, 'thumb']) }}" alt="" class="mt-1 max-h-60 rounded-lg"></a>
                                    @else
                                        <a href="{{ route('media.show', $file) }}" target="_blank" class="mt-1 flex items-center gap-1 underline"><x-heroicon-o-paper-clip class="size-4" /> {{ $file->file_name }}</a>
                                    @endif
                                @endforeach
                            @endif
                            <div @class(['mt-0.5 flex items-center gap-2 text-[10px]', 'text-white/70' => $mine, 'text-medi-gray/70' => ! $mine])>
                                {{ $msg->created_at->format('d.m. H:i') }}
                                @if (! $msg->deleted_at && ($mine || $isModerator))
                                    <button wire:click="deleteMessage({{ $msg->id }})" wire:confirm="Nachricht löschen?" class="hidden underline group-hover:inline">löschen</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm">Noch keine Nachrichten. Schreiben Sie die erste!</p>
                @endforelse
            </div>

            @if ($canWrite)
                <form wire:submit="send" class="border-t border-black/5 p-3">
                    @if ($upload)<p class="mb-2 flex items-center gap-1 text-xs"><x-heroicon-o-paper-clip class="size-4" /> {{ $upload->getClientOriginalName() }} <button type="button" wire:click="$set('upload', null)" class="text-medi-red">entfernen</button></p>@endif
                    @error('body')<p class="mb-1 text-xs text-medi-red">{{ $message }}</p>@enderror
                    @error('upload')<p class="mb-1 text-xs text-medi-red">{{ $message }}</p>@enderror
                    <div class="flex items-end gap-2">
                        <label class="cursor-pointer rounded-xl p-2.5 text-medi-gray hover:bg-medi-bg" aria-label="Datei anhängen">
                            <x-heroicon-o-paper-clip class="size-5" />
                            <input type="file" wire:model="upload" class="sr-only">
                        </label>
                        <textarea wire:model="body" rows="1" class="input max-h-32 min-h-11 resize-none" placeholder="Nachricht schreiben"
                                  @keydown.enter="if (!$event.shiftKey && window.innerWidth > 768) { $event.preventDefault(); $wire.send() }"></textarea>
                        <button class="btn px-3" aria-label="Senden" wire:loading.attr="disabled"><x-heroicon-o-paper-airplane class="size-5" /></button>
                    </div>
                </form>
            @else
                <p class="border-t border-black/5 p-4 text-center text-xs">In diesem Kanal schreibt nur die Moderation.</p>
            @endif
        @else
            <div class="grid flex-1 place-items-center p-8 text-center text-sm">
                <div>
                    <x-heroicon-o-chat-bubble-left-right class="mx-auto mb-3 size-12 text-medi-blue/30" />
                    Wählen Sie links eine Gruppe oder starten Sie einen Direktchat.
                </div>
            </div>
        @endif
    </section>
</div>
