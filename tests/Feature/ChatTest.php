<?php

namespace Tests\Feature;

use App\Livewire\Chat;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Services\ChatGroupSync;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    public function test_automatische_gruppen_und_mitgliedschaften(): void
    {
        $a = $this->person('Fürth', ['Physiotherapie']);
        $b = $this->person('Roth', ['Physiotherapie']);
        app(ChatGroupSync::class)->run();

        $fuerth = ChatGroup::where('type', 'location')->where('location_id', $this->location('Fürth')->id)->first();
        $physio = ChatGroup::where('type', 'department')->where('department_id', $this->department('Physiotherapie')->id)->first();
        $this->assertTrue($fuerth->members()->where('user_id', $a->id)->exists());
        $this->assertFalse($fuerth->members()->where('user_id', $b->id)->exists());
        $this->assertSame(2, $physio->members()->count());
        $this->assertSame(2, ChatGroup::where('type', 'announcement')->first()->members()->count());
    }

    public function test_ankuendigungskanal_nur_fuer_moderation(): void
    {
        $user = $this->person();
        app(ChatGroupSync::class)->run();
        $announcement = ChatGroup::where('type', 'announcement')->first();

        $this->assertFalse($announcement->canWrite($user));
        Livewire::actingAs($user)->test(Chat::class, ['group' => $announcement])->set('body', 'Hallo')->call('send')->assertForbidden();
    }

    public function test_nachricht_senden_und_ungelesen_zaehlen(): void
    {
        $a = $this->person();
        $b = $this->person();
        $group = ChatGroup::directBetween($a, $b);
        $this->assertSame($group->id, ChatGroup::directBetween($b, $a)->id, 'kein doppelter Direktchat');

        Livewire::actingAs($a)->test(Chat::class, ['group' => $group])->set('body', 'Hallo B')->call('send')->assertHasNoErrors();
        $this->assertSame(1, ChatMessage::count());
        $this->assertSame(1, $group->fresh()->unreadCountFor($b));
        $this->assertSame(0, $group->fresh()->unreadCountFor($a));
    }

    public function test_mitarbeiter_legt_gruppe_an_und_benennt_sie_um(): void
    {
        $a = $this->person();
        $b = $this->person();
        $other = $this->person();

        Livewire::actingAs($a)->test(Chat::class)
            ->call('openNewGroup')->assertSet('newGroup', true)
            ->set('groupName', 'Lauftreff')->call('addMember', $b->id)->call('createGroup')->assertHasNoErrors();

        $group = ChatGroup::where('name', 'Lauftreff')->firstOrFail();
        $this->assertSame('custom', $group->type);
        $this->assertTrue($group->membership($a)->is_moderator);
        $this->assertFalse($group->membership($b)->is_moderator);

        Livewire::actingAs($b)->test(Chat::class, ['group' => $group])->call('startRename')->assertForbidden();
        Livewire::actingAs($a)->test(Chat::class, ['group' => $group])
            ->call('startRename')->set('newName', 'Lauftreff Fürth')->call('rename')->assertHasNoErrors();
        $this->assertSame('Lauftreff Fürth', $group->fresh()->name);
        $this->assertNull($group->fresh()->membership($other));
    }

    public function test_fremde_gruppe_ist_gesperrt(): void
    {
        $group = ChatGroup::directBetween($this->person(), $this->person());
        $this->actingAs($this->person())->get(route('chat', $group))->assertForbidden();
    }

    public function test_direktchat_mit_noch_nicht_angemeldeter_person(): void
    {
        $a = $this->person();
        $max = $this->person('Fürth', ['Physiotherapie'], ['first_name' => 'Max', 'last_name' => 'Muster', 'status' => 'invited']);

        Livewire::actingAs($a)->test(Chat::class)->call('openNewChat')->set('personSearch', 'Max')->assertSee('Max Muster');
        Livewire::actingAs($a)->withQueryParams(['direct' => $max->id])->test(Chat::class)->assertRedirect();
        $this->assertNotNull(ChatGroup::where('type', 'direct')->whereHas('members', fn ($q) => $q->where('user_id', $max->id))->first());
    }

    public function test_einladen_nur_moderation_ausser_freigegeben(): void
    {
        $owner = $this->person();
        $member = $this->person();
        $guest = $this->person();
        $group = ChatGroup::create(['type' => 'custom', 'name' => 'Lauftreff']);
        $group->members()->createMany([['user_id' => $owner->id, 'is_moderator' => true], ['user_id' => $member->id]]);

        Livewire::actingAs($member)->test(Chat::class, ['group' => $group])->call('invite', $guest->id)->assertForbidden();
        Livewire::actingAs($member)->test(Chat::class, ['group' => $group])->call('toggleMembersCanInvite')->assertForbidden();
        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group])->call('toggleMembersCanInvite');
        $this->assertTrue($group->fresh()->members_can_invite);
        Livewire::actingAs($member)->test(Chat::class, ['group' => $group->fresh()])->call('invite', $guest->id)->assertHasNoErrors();
        $this->assertNotNull($group->fresh()->membership($guest));
    }

    public function test_suche_findet_nachrichten_nur_in_eigenen_gruppen(): void
    {
        $a = $this->person();
        $b = $this->person();
        $c = $this->person();
        $mine = ChatGroup::directBetween($a, $b);
        $other = ChatGroup::directBetween($b, $c);
        ChatMessage::create(['chat_group_id' => $mine->id, 'user_id' => $b->id, 'body' => 'Dienstplan Dezember ist fertig']);
        ChatMessage::create(['chat_group_id' => $other->id, 'user_id' => $c->id, 'body' => 'Dienstplan geheim']);

        Livewire::actingAs($a)->test(Chat::class)->set('search', 'Dienstplan')
            ->assertSee('Dienstplan Dezember ist fertig')->assertDontSee('Dienstplan geheim');
    }

    public function test_moderation_entfernt_mitglieder(): void
    {
        $owner = $this->person();
        $member = $this->person();
        $other = $this->person();
        $group = ChatGroup::create(['type' => 'custom', 'name' => 'Lauftreff', 'members_can_invite' => true]);
        $group->members()->createMany([['user_id' => $owner->id, 'is_moderator' => true], ['user_id' => $member->id], ['user_id' => $other->id]]);

        // Einladen dürfen alle, entfernen trotzdem nur die Moderation
        Livewire::actingAs($member)->test(Chat::class, ['group' => $group])->call('removeFromGroup', $other->id)->assertForbidden();
        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group])->call('removeFromGroup', $owner->id)->assertForbidden();
        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group])->call('removeFromGroup', $other->id);
        $this->assertNull($group->fresh()->membership($other));
        $this->assertTrue($group->messages()->where('body', 'like', '%aus der Gruppe entfernt%')->exists());
        Livewire::actingAs($other)->test(Chat::class, ['group' => $group->fresh()])->assertForbidden();
    }

    public function test_ohne_verlauf_sehen_neue_mitglieder_nur_neue_nachrichten(): void
    {
        $owner = $this->person();
        $guest = $this->person();
        $group = ChatGroup::create(['type' => 'custom', 'name' => 'Projekt']);
        $member = $this->person();
        $group->members()->createMany([['user_id' => $owner->id, 'is_moderator' => true], ['user_id' => $member->id]]);
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $owner->id, 'body' => 'Alte Planung vertraulich']);

        Livewire::actingAs($member)->test(Chat::class, ['group' => $group])->call('toggleHistoryForNewMembers')->assertForbidden();
        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group])->call('toggleHistoryForNewMembers');
        $this->assertFalse($group->fresh()->history_for_new_members);

        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group->fresh()])->call('invite', $guest->id);
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $owner->id, 'body' => 'Willkommen im Projekt']);

        $group = $group->fresh();
        $this->assertSame(1, $group->unreadCountFor($guest), 'alte Nachrichten zählen nicht als ungelesen');
        Livewire::actingAs($guest)->test(Chat::class, ['group' => $group])
            ->assertSee('Willkommen im Projekt')->assertDontSee('Alte Planung vertraulich');
        Livewire::actingAs($guest)->test(Chat::class)->set('search', 'Planung')->assertDontSee('Alte Planung vertraulich');
    }

    public function test_mit_verlauf_sehen_neue_mitglieder_alles(): void
    {
        $owner = $this->person();
        $guest = $this->person();
        $group = ChatGroup::create(['type' => 'custom', 'name' => 'Projekt']);
        $this->assertTrue($group->fresh()->history_for_new_members, 'Standard: Verlauf sichtbar');
        $group->members()->create(['user_id' => $owner->id, 'is_moderator' => true]);
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $owner->id, 'body' => 'Alte Planung offen']);
        Livewire::actingAs($owner)->test(Chat::class, ['group' => $group->fresh()])->call('invite', $guest->id);

        Livewire::actingAs($guest)->test(Chat::class, ['group' => $group->fresh()])->assertSee('Alte Planung offen');
    }
}
