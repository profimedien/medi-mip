<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    public const TYPES = [
        'announcement' => 'Ankündigungen',
        'location' => 'Standort',
        'department' => 'Abteilung',
        'custom' => 'Gruppe',
        'direct' => 'Direktchat',
    ];

    protected $guarded = ['id'];

    protected $casts = ['read_only' => 'boolean', 'members_can_invite' => 'boolean', 'history_for_new_members' => 'boolean', 'retention_days' => 'integer', 'last_message_at' => 'datetime'];

    /** Speicherdauer in Tagen: Gruppenwert, sonst globale Einstellung. null = dauerhaft */
    public function effectiveRetentionDays(): ?int
    {
        $days = $this->retention_days ?? (int) Setting::get('chat_retention_days', 365);

        return $days > 0 ? $days : null;
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_members')->withPivot(['is_moderator', 'last_read_message_id']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
    }

    public function membership(User $user): ?ChatMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }

    public function canWrite(User $user): bool
    {
        $member = $this->membership($user);
        if (! $member) {
            return false;
        }

        return ! $this->read_only || $member->is_moderator || $user->isGesamtAdmin();
    }

    /** Eigene Gruppen: Moderation lädt immer ein, alle Mitglieder nur, wenn die Moderation es erlaubt */
    public function canInvite(User $user): bool
    {
        if ($this->type !== 'custom') {
            return false;
        }
        $member = $this->membership($user);

        return ($member && ($member->is_moderator || $this->members_can_invite)) || $user->isGesamtAdmin();
    }

    /** Moderation einer eigenen Gruppe: Ersteller bzw. Moderatoren und Gesamt-Admin */
    public function canModerate(User $user): bool
    {
        return $this->type === 'custom' && ($this->membership($user)?->is_moderator || $user->isGesamtAdmin());
    }

    /** Mitglieder entfernen darf nur die Moderation, sich selbst nicht (dafür: Verlassen) */
    public function canRemoveMember(User $user, ChatMember $member): bool
    {
        return $member->user_id !== $user->id && $this->canModerate($user);
    }

    /**
     * Nachträglich hinzufügen. Ohne Verlauf sieht die Person nur Nachrichten ab jetzt,
     * und ältere Nachrichten zählen nicht als ungelesen.
     */
    public function addMember(User $person): ChatMember
    {
        $lastId = $this->history_for_new_members ? 0 : (int) $this->messages()->max('id');

        return $this->members()->create([
            'user_id' => $person->id,
            'visible_from_message_id' => $lastId,
            'last_read_message_id' => $lastId,
        ]);
    }

    /** Nachrichten, die diese Person sehen darf (Verlauf-Grenze bei nachträglichem Beitritt) */
    public function visibleMessagesFor(User $user)
    {
        return $this->messages()->where('id', '>', $this->membership($user)?->visible_from_message_id ?? 0);
    }

    /** Anzeigename; bei Direktchats der Name des Gegenübers */
    public function titleFor(User $user): string
    {
        if ($this->type === 'direct') {
            return $this->users->firstWhere('id', '!=', $user->id)?->name ?? 'Direktchat';
        }

        return $this->name ?? 'Gruppe';
    }

    public function unreadCountFor(User $user): int
    {
        $lastRead = $this->members->firstWhere('user_id', $user->id)?->last_read_message_id ?? 0;

        return $this->messages()->where('id', '>', $lastRead)->whereNull('deleted_at')->where('user_id', '!=', $user->id)->count();
    }

    public static function directBetween(User $a, User $b): self
    {
        $existing = static::where('type', 'direct')
            ->whereHas('members', fn ($q) => $q->where('user_id', $a->id))
            ->whereHas('members', fn ($q) => $q->where('user_id', $b->id))
            ->first();
        if ($existing) {
            return $existing;
        }
        $group = static::create(['type' => 'direct']);
        $group->members()->createMany([['user_id' => $a->id], ['user_id' => $b->id]]);

        return $group;
    }
}
