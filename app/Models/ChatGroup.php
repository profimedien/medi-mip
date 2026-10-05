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

    protected $casts = ['read_only' => 'boolean', 'last_message_at' => 'datetime'];

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
