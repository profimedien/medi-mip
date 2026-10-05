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
}
