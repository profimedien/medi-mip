<?php

namespace App\Services;

use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;

/**
 * Hält die automatischen Chatgruppen aktuell:
 * eine Ankündigungsgruppe für alle, je Standort und je Abteilung eine Gruppe.
 * Manuelle Gruppen und Direktchats bleiben unberührt.
 */
class ChatGroupSync
{
    public function run(): void
    {
        $users = User::where('status', '!=', 'disabled')->with('departments:id')->get();
        $admins = $users->filter->isAdmin()->pluck('id')->all();

        $announcement = ChatGroup::firstOrCreate(
            ['type' => 'announcement'],
            ['name' => 'medi Gruppe · Ankündigungen', 'read_only' => true],
        );
        $this->syncMembers($announcement, $users->pluck('id')->all(), $admins);

        foreach (Location::where('active', true)->get() as $location) {
            $group = ChatGroup::firstOrCreate(
                ['type' => 'location', 'location_id' => $location->id],
                ['name' => $location->label],
            );
            $group->update(['name' => $location->label]);
            $this->syncMembers($group, $users->where('location_id', $location->id)->pluck('id')->all(), $this->scopedAdmins(location: $location->id));
        }

        foreach (Department::all() as $department) {
            $group = ChatGroup::firstOrCreate(
                ['type' => 'department', 'department_id' => $department->id],
                ['name' => $department->name],
            );
            $group->update(['name' => $department->name]);
            $memberIds = $users->filter(fn ($u) => $u->departments->contains('id', $department->id))->pluck('id')->all();
            $this->syncMembers($group, $memberIds, $this->scopedAdmins(department: $department->id));
        }
    }

    private function scopedAdmins(?int $location = null, ?int $department = null): array
    {
        return User::role('gesamt-admin')->pluck('id')
            ->merge(User::whereHas('adminScopes', fn ($q) => $location ? $q->where('location_id', $location) : $q->where('department_id', $department))->pluck('id'))
            ->unique()->all();
    }

    private function syncMembers(ChatGroup $group, array $userIds, array $moderatorIds): void
    {
        $userIds = array_values(array_unique(array_merge($userIds, $moderatorIds)));

        ChatMember::where('chat_group_id', $group->id)->whereNotIn('user_id', $userIds)->delete();
        $existing = ChatMember::where('chat_group_id', $group->id)->pluck('user_id')->all();

        foreach (array_diff($userIds, $existing) as $userId) {
            ChatMember::create(['chat_group_id' => $group->id, 'user_id' => $userId]);
        }
        ChatMember::where('chat_group_id', $group->id)->update(['is_moderator' => false]);
        ChatMember::where('chat_group_id', $group->id)->whereIn('user_id', $moderatorIds)->update(['is_moderator' => true]);
    }
}
