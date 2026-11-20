<?php

namespace App\Livewire;

use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Models\User;
use App\Services\ChatPruner;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Chat')]
class Chat extends Component
{
    use WithFileUploads;

    public ?ChatGroup $group = null;

    #[Url]
    public ?int $direct = null;

    public string $body = '';

    public $upload = null;

    public string $personSearch = '';

    public bool $newChat = false;

    public bool $newGroup = false;

    public string $groupName = '';

    public array $groupMembers = [];

    public string $memberSearch = '';

    public bool $membersCanInvite = false;

    public bool $historyForNewMembers = true;

    public bool $showMembers = false;

    public string $search = '';

    public bool $inviting = false;

    public string $inviteSearch = '';

    public bool $renaming = false;

    public string $newName = '';

    public int $limit = 50;

    public bool $showRules = false;

    public function mount(?ChatGroup $group = null): void
    {
        $user = auth()->user();
        ChatPruner::runIfDue(); // Speicherdauer: ohne Cronjob beim Aufruf, höchstens einmal am Tag

        // Chatregeln beim ersten Öffnen einmal zeigen
        $this->showRules = ! $user->chat_rules_seen_at && filled(Setting::get('chat_rules'));

        if ($this->direct && $this->direct !== $user->id) {
            // auch Kolleginnen und Kollegen, die sich noch nicht angemeldet haben (Status "eingeladen"): Nachricht wartet auf sie
            $other = User::where('status', '!=', 'disabled')->findOrFail($this->direct);
            $this->redirectRoute('chat', ['group' => ChatGroup::directBetween($user, $other)], navigate: true);

            return;
        }

        if ($group?->exists) {
            abort_unless($group->members()->where('user_id', $user->id)->exists(), 403);
            $this->group = $group;
            $this->markRead();
        }
    }

    public static function uploadsAllowed(): bool
    {
        return (bool) Setting::get('chat_uploads', false);
    }

    public function toggleRules(): void
    {
        $this->showRules = ! $this->showRules;
    }

    /** Chatregeln gelesen: Hinweis erscheint nicht mehr von selbst */
    public function acceptRules(): void
    {
        auth()->user()->forceFill(['chat_rules_seen_at' => now()])->save();
        $this->showRules = false;
    }

    public function markRead(): void
    {
        if (! $this->group) {
            return;
        }
        $last = $this->group->messages()->max('id') ?? 0;
        ChatMember::where('chat_group_id', $this->group->id)->where('user_id', auth()->id())
            ->where('last_read_message_id', '<', $last)->update(['last_read_message_id' => $last]);
    }

    public function send(): void
    {
        abort_unless($this->group && $this->group->canWrite(auth()->user()), 403);
        if ($this->upload && ! self::uploadsAllowed()) {
            $this->reset('upload');
            $this->addError('upload', 'Bilder und Dateien sind im Chat derzeit nicht freigeschaltet.');

            return;
        }
        $this->validate([
            'body' => ['nullable', 'string', 'max:4000', 'required_without:upload'],
            'upload' => ['nullable', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp,heic,pdf,doc,docx,xls,xlsx'],
        ], ['body.required_without' => 'Bitte eine Nachricht schreiben oder eine Datei anhängen.']);

        $message = ChatMessage::create([
            'chat_group_id' => $this->group->id,
            'user_id' => auth()->id(),
            'body' => trim($this->body) ?: null,
        ]);
        if ($this->upload) {
            $message->addMedia($this->upload->getRealPath())->usingFileName($this->upload->getClientOriginalName())->toMediaCollection('files');
        }
        $this->group->update(['last_message_at' => now()]);
        $this->reset('body', 'upload');
        $this->markRead();
        $this->dispatch('chat-sent');
    }

    public function openNewChat(): void
    {
        $this->newGroup = false;
        $this->newChat = ! $this->newChat;
    }

    public function openNewGroup(): void
    {
        $this->newChat = false;
        $this->newGroup = ! $this->newGroup;
        $this->resetErrorBag();
    }

    public function startRename(): void
    {
        abort_unless($this->canRename(), 403);
        $this->newName = (string) $this->group->name;
        $this->renaming = true;
    }

    /** Eigene Gruppen: Moderation oder Gesamt-Admin darf umbenennen */
    public function rename(): void
    {
        abort_unless($this->canRename(), 403);
        $this->validate(['newName' => ['required', 'string', 'min:3', 'max:60']], [], ['newName' => 'Gruppenname']);
        $old = $this->group->name;
        $this->group->update(['name' => $this->newName]);
        ChatMessage::create(['chat_group_id' => $this->group->id, 'user_id' => auth()->id(),
            'body' => auth()->user()->first_name.' hat die Gruppe von „'.$old.'“ in „'.$this->newName.'“ umbenannt.']);
        $this->group->update(['last_message_at' => now()]);
        $this->renaming = false;
    }

    private function canRename(): bool
    {
        $user = auth()->user();

        return $this->group && $this->group->type === 'custom'
            && ($this->group->membership($user)?->is_moderator || $user->isGesamtAdmin());
    }

    public function addMember(int $id): void
    {
        if (! in_array($id, $this->groupMembers, true)) {
            $this->groupMembers[] = $id;
        }
        $this->memberSearch = '';
    }

    public function removeMember(int $id): void
    {
        $this->groupMembers = array_values(array_diff($this->groupMembers, [$id]));
    }

    /** Eigene Gruppe: wer sie anlegt, moderiert sie */
    public function createGroup()
    {
        $this->validate([
            'groupName' => ['required', 'string', 'min:3', 'max:60'],
            'groupMembers' => ['required', 'array', 'min:1'],
            'groupMembers.*' => ['integer', 'exists:users,id'],
        ], ['groupMembers.required' => 'Bitte mindestens eine Person hinzufügen.'], ['groupName' => 'Gruppenname']);

        $group = ChatGroup::create(['type' => 'custom', 'name' => $this->groupName, 'members_can_invite' => $this->membersCanInvite, 'history_for_new_members' => $this->historyForNewMembers, 'last_message_at' => now()]);
        ChatMember::create(['chat_group_id' => $group->id, 'user_id' => auth()->id(), 'is_moderator' => true]);
        foreach (array_unique($this->groupMembers) as $id) {
            if ($id !== auth()->id()) {
                ChatMember::create(['chat_group_id' => $group->id, 'user_id' => $id]);
            }
        }
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => auth()->id(), 'body' => auth()->user()->first_name.' hat die Gruppe „'.$this->groupName.'“ erstellt.']);
        $this->reset('newGroup', 'groupName', 'groupMembers', 'memberSearch', 'membersCanInvite', 'historyForNewMembers');

        return $this->redirectRoute('chat', ['group' => $group], navigate: true);
    }

    public function openInvite(): void
    {
        abort_unless($this->group?->canInvite(auth()->user()), 403);
        $this->inviting = ! $this->inviting;
        $this->inviteSearch = '';
    }

    /** Person in eine bestehende eigene Gruppe holen */
    public function invite(int $id): void
    {
        $user = auth()->user();
        abort_unless($this->group?->canInvite($user), 403);
        $person = User::where('status', '!=', 'disabled')->findOrFail($id);
        if (! $this->group->members()->where('user_id', $person->id)->exists()) {
            $this->group->addMember($person);
            ChatMessage::create(['chat_group_id' => $this->group->id, 'user_id' => $user->id,
                'body' => $user->first_name.' hat '.$person->name.' zur Gruppe hinzugefügt.']);
            $this->group->update(['last_message_at' => now()]);
        }
        $this->inviteSearch = '';
    }

    /** Nur die Moderation legt fest, ob alle Mitglieder einladen dürfen */
    public function toggleMembersCanInvite(): void
    {
        $user = auth()->user();
        abort_unless($this->group && $this->group->type === 'custom'
            && ($this->group->membership($user)?->is_moderator || $user->isGesamtAdmin()), 403);
        $this->group->update(['members_can_invite' => ! $this->group->members_can_invite]);
    }

    /** Nur die Moderation legt fest, ob nachträglich Hinzugefügte den bisherigen Verlauf sehen */
    public function toggleHistoryForNewMembers(): void
    {
        abort_unless($this->group?->canModerate(auth()->user()), 403);
        $this->group->update(['history_for_new_members' => ! $this->group->history_for_new_members]);
    }

    public function toggleMembers(): void
    {
        $this->showMembers = ! $this->showMembers;
    }

    /** Mitglied aus einer eigenen Gruppe entfernen (nur Moderation) */
    public function removeFromGroup(int $userId): void
    {
        $user = auth()->user();
        $member = $this->group?->members()->where('user_id', $userId)->with('user')->firstOrFail();
        abort_unless($member && $this->group->canRemoveMember($user, $member), 403);
        $member->delete();
        ChatMessage::create(['chat_group_id' => $this->group->id, 'user_id' => $user->id,
            'body' => $user->first_name.' hat '.$member->user->name.' aus der Gruppe entfernt.']);
        $this->group->update(['last_message_at' => now()]);
    }

    public function leaveGroup()
    {
        abort_unless($this->group && $this->group->type === 'custom', 403);
        ChatMember::where('chat_group_id', $this->group->id)->where('user_id', auth()->id())->delete();

        return $this->redirectRoute('chat', navigate: true);
    }

    public function deleteMessage(int $id): void
    {
        $message = ChatMessage::where('chat_group_id', $this->group?->id)->findOrFail($id);
        $user = auth()->user();
        $member = $this->group->membership($user);
        abort_unless($message->user_id === $user->id || $member?->is_moderator || $user->isGesamtAdmin(), 403);
        $message->update(['deleted_at' => now(), 'deleted_by' => $user->id, 'body' => null]);
        $message->clearMediaCollection('files');
    }

    public function render()
    {
        $user = auth()->user();
        $groups = ChatGroup::forUser($user)->with(['users', 'members'])
            ->orderByRaw("type = 'announcement' desc")->orderByDesc('last_message_at')->orderBy('name')->get();

        // Suche: eigene Gruppen und Direktchats nach Name, dazu alle Nachrichten in diesen Unterhaltungen
        $term = trim($this->search);
        $foundMessages = collect();
        if (mb_strlen($term) >= 2) {
            $needle = mb_strtolower($term);
            // Text liegt verschlüsselt in der Datenbank: die letzten Nachrichten der eigenen Unterhaltungen laden und nach dem Entschlüsseln filtern
            $foundMessages = ChatMessage::query()
                ->join('chat_members as cm', fn ($j) => $j->on('cm.chat_group_id', '=', 'chat_messages.chat_group_id')->where('cm.user_id', $user->id))
                ->whereColumn('chat_messages.id', '>', 'cm.visible_from_message_id')
                ->select('chat_messages.*')
                ->whereNull('chat_messages.deleted_at')
                ->whereNotNull('chat_messages.body')
                ->latest('chat_messages.id')->limit(2000)->get()
                ->filter(fn (ChatMessage $m) => str_contains(mb_strtolower((string) $m->body), $needle))
                ->take(30)->values()
                ->load('user', 'group.users');
            $groups = $groups->filter(fn (ChatGroup $g) => str_contains(mb_strtolower($g->titleFor($user)), $needle))->values();
        }

        $messages = collect();
        if ($this->group) {
            $messages = $this->group->visibleMessagesFor($user)->with('user', 'media')->latest('id')->limit($this->limit)->get()->reverse()->values();
            $this->markRead();
        }

        $people = $this->newChat && strlen($this->personSearch) >= 2
            ? User::where('status', '!=', 'disabled')->where('id', '!=', $user->id)
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->personSearch}%")->orWhere('last_name', 'like', "%{$this->personSearch}%"))
                ->with('location')->limit(8)->get()
            : collect();

        $memberResults = $this->newGroup && strlen($this->memberSearch) >= 2
            ? User::where('status', '!=', 'disabled')->where('id', '!=', $user->id)->whereNotIn('id', $this->groupMembers)
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->memberSearch}%")->orWhere('last_name', 'like', "%{$this->memberSearch}%"))
                ->with('location')->limit(6)->get()
            : collect();

        $inviteResults = $this->inviting && $this->group && strlen($this->inviteSearch) >= 2
            ? User::where('status', '!=', 'disabled')->whereNotIn('id', $this->group->members()->pluck('user_id'))
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->inviteSearch}%")->orWhere('last_name', 'like', "%{$this->inviteSearch}%"))
                ->with('location')->limit(6)->get()
            : collect();

        return view('livewire.chat', [
            'foundMessages' => $foundMessages,
            'uploadsAllowed' => self::uploadsAllowed(),
            'chatRules' => (string) Setting::get('chat_rules', ''),
            'retentionDays' => $this->group?->effectiveRetentionDays(),
            'inviteResults' => $inviteResults,
            'canInvite' => $this->group?->canInvite($user) ?? false,
            'canModerate' => $this->group?->canModerate($user) ?? false,
            'memberList' => $this->showMembers && $this->group ? $this->group->members()->with('user.location')->get()->sortBy(fn ($m) => [! $m->is_moderator, $m->user->last_name])->values() : collect(),
            'memberResults' => $memberResults,
            'selectedMembers' => User::whereIn('id', $this->groupMembers)->get(),
            'groups' => $groups,
            'messages' => $messages,
            'people' => $people,
            'canWrite' => $this->group?->canWrite($user) ?? false,
            'canRename' => $this->canRename(),
            'isModerator' => $this->group ? ($this->group->membership($user)?->is_moderator || $user->isGesamtAdmin()) : false,
        ]);
    }
}
