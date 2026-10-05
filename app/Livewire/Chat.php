<?php

namespace App\Livewire;

use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Medigram')]
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

    public int $limit = 50;

    public function mount(?ChatGroup $group = null): void
    {
        $user = auth()->user();

        if ($this->direct && $this->direct !== $user->id) {
            $other = User::where('status', 'active')->findOrFail($this->direct);
            $this->redirectRoute('chat', ['group' => ChatGroup::directBetween($user, $other)], navigate: true);

            return;
        }

        if ($group?->exists) {
            abort_unless($group->members()->where('user_id', $user->id)->exists(), 403);
            $this->group = $group;
            $this->markRead();
        }
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

        $group = ChatGroup::create(['type' => 'custom', 'name' => $this->groupName, 'last_message_at' => now()]);
        ChatMember::create(['chat_group_id' => $group->id, 'user_id' => auth()->id(), 'is_moderator' => true]);
        foreach (array_unique($this->groupMembers) as $id) {
            if ($id !== auth()->id()) {
                ChatMember::create(['chat_group_id' => $group->id, 'user_id' => $id]);
            }
        }
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => auth()->id(), 'body' => auth()->user()->first_name.' hat die Gruppe „'.$this->groupName.'“ erstellt.']);
        $this->reset('newGroup', 'groupName', 'groupMembers', 'memberSearch');

        return $this->redirectRoute('chat', ['group' => $group], navigate: true);
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

        $messages = collect();
        if ($this->group) {
            $messages = $this->group->messages()->with('user', 'media')->latest('id')->limit($this->limit)->get()->reverse()->values();
            $this->markRead();
        }

        $people = $this->newChat && strlen($this->personSearch) >= 2
            ? User::where('status', 'active')->where('id', '!=', $user->id)
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->personSearch}%")->orWhere('last_name', 'like', "%{$this->personSearch}%"))
                ->with('location')->limit(8)->get()
            : collect();

        $memberResults = $this->newGroup && strlen($this->memberSearch) >= 2
            ? User::where('status', 'active')->where('id', '!=', $user->id)->whereNotIn('id', $this->groupMembers)
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->memberSearch}%")->orWhere('last_name', 'like', "%{$this->memberSearch}%"))
                ->with('location')->limit(6)->get()
            : collect();

        return view('livewire.chat', [
            'memberResults' => $memberResults,
            'selectedMembers' => User::whereIn('id', $this->groupMembers)->get(),
            'groups' => $groups,
            'messages' => $messages,
            'people' => $people,
            'canWrite' => $this->group?->canWrite($user) ?? false,
            'isModerator' => $this->group ? ($this->group->membership($user)?->is_moderator || $user->isGesamtAdmin()) : false,
        ]);
    }
}
