<?php

namespace Tests\Feature;

use App\Livewire\Chat;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Services\ChatGroupSync;
use App\Services\ChatPruner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Chat-Punkte medi 20.11.2026: Upload-Schalter, Speicherdauer, Verschlüsselung, Chatregeln, Chat-Übersicht */
class ChatPunkteTest extends TestCase
{
    public function test_upload_nur_wenn_freigeschaltet(): void
    {
        $a = $this->person();
        $group = ChatGroup::directBetween($a, $this->person());

        Setting::set('chat_uploads', false);
        Livewire::actingAs($a)->test(Chat::class, ['group' => $group])
            ->assertDontSee('Datei anhängen')
            ->set('upload', UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf'))->call('send')
            ->assertHasErrors('upload');
        $this->assertSame(0, ChatMessage::count());

        Setting::set('chat_uploads', true);
        Livewire::actingAs($a)->test(Chat::class, ['group' => $group])
            ->assertSee('Datei anhängen')
            ->set('upload', UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf'))->call('send')
            ->assertHasNoErrors();
        $this->assertSame(1, ChatMessage::count());
    }

    public function test_speicherdauer_global_und_je_gruppe(): void
    {
        $a = $this->person();
        $b = $this->person();
        $standard = ChatGroup::directBetween($a, $b);
        $dauerhaft = ChatGroup::create(['type' => 'custom', 'name' => 'Archiv', 'retention_days' => 0]);
        $kurz = ChatGroup::create(['type' => 'custom', 'name' => 'Kurz', 'retention_days' => 30]);

        $alt = fn (ChatGroup $g, int $tage) => tap(ChatMessage::create(['chat_group_id' => $g->id, 'user_id' => $a->id, 'body' => 'x']))
            ->forceFill(['created_at' => now()->subDays($tage)])->save();
        $alt($standard, 400);
        $alt($standard, 100);
        $alt($dauerhaft, 2000);
        $alt($kurz, 40);
        $alt($kurz, 10);

        $this->assertSame(2, app(ChatPruner::class)->run()); // 400 Tage (Standard 365) und 40 Tage (Gruppe 30)
        $this->assertSame(1, $standard->messages()->count());
        $this->assertSame(1, $dauerhaft->messages()->count());
        $this->assertSame(1, $kurz->messages()->count());

        Setting::set('chat_retention_days', 0); // global dauerhaft
        $alt($standard, 5000);
        $this->assertSame(0, app(ChatPruner::class)->run());
    }

    public function test_loeschung_ohne_cron_hoechstens_einmal_am_tag(): void
    {
        $a = $this->person();
        $group = ChatGroup::directBetween($a, $this->person());
        $msg = ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $a->id, 'body' => 'alt']);
        $msg->forceFill(['created_at' => now()->subDays(400)])->save();

        Livewire::actingAs($a)->test(Chat::class);
        $this->assertSame(0, ChatMessage::count());

        $msg = ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $a->id, 'body' => 'alt']);
        $msg->forceFill(['created_at' => now()->subDays(400)])->save();
        ChatPruner::runIfDue();
        $this->assertSame(1, ChatMessage::count(), 'am selben Tag kein zweiter Lauf');
    }

    public function test_text_verschluesselt_gespeichert_und_durchsuchbar(): void
    {
        $a = $this->person();
        $group = ChatGroup::directBetween($a, $this->person());
        Livewire::actingAs($a)->test(Chat::class, ['group' => $group])->set('body', 'Dienstplan Dezember')->call('send');

        $raw = DB::table('chat_messages')->value('body');
        $this->assertStringNotContainsString('Dienstplan', $raw);
        $this->assertSame('Dienstplan Dezember', ChatMessage::first()->body);

        Livewire::actingAs($a)->test(Chat::class)->set('search', 'dienstplan')->assertSee('Dienstplan Dezember');
    }

    public function test_chatregeln_einmal_zeigen(): void
    {
        $a = $this->person();
        Livewire::actingAs($a)->test(Chat::class)->assertSet('showRules', false);

        Setting::set('chat_rules', 'Freundlich bleiben.');
        Livewire::actingAs($a)->test(Chat::class)->assertSet('showRules', true)->assertSee('Freundlich bleiben.')
            ->call('acceptRules')->assertSet('showRules', false);
        $this->assertNotNull($a->fresh()->chat_rules_seen_at);
        Livewire::actingAs($a->fresh())->test(Chat::class)->assertSet('showRules', false);
    }

    public function test_chat_uebersicht_sieht_liste_ohne_inhalte(): void
    {
        $member = $this->person('Fürth');
        $gl = $this->person('Verwaltung', ['Geschäftsleitung']);
        $gl->syncRoles(['chat-uebersicht']);
        $group = ChatGroup::create(['type' => 'custom', 'name' => 'Lauftreff Fürth']);
        $group->members()->create(['user_id' => $member->id, 'is_moderator' => true]);
        ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $member->id, 'body' => 'Geheimer Treffpunkt']);

        $this->actingAs($gl)->get('/admin/chat-groups')->assertOk()->assertSee('Lauftreff Fürth')->assertDontSee('Geheimer Treffpunkt');
        $this->actingAs($gl)->get('/admin/posts')->assertForbidden();
        $this->actingAs($gl)->get(route('chat', $group))->assertForbidden();
    }

    public function test_gesamt_admin_nicht_automatisch_in_allen_gruppen(): void
    {
        $this->person('Fürth');
        $admin = $this->person('Roth', ['Ergotherapie']);
        $admin->syncRoles(['gesamt-admin']);
        app(ChatGroupSync::class)->run();

        $fuerth = ChatGroup::where('type', 'location')->where('location_id', $this->location('Fürth')->id)->first();
        $roth = ChatGroup::where('type', 'location')->where('location_id', $this->location('Roth')->id)->first();
        $this->assertNull($fuerth->membership($admin));
        $this->assertNotNull($roth->membership($admin), 'eigener Standort bleibt');
        $this->assertTrue(ChatGroup::where('type', 'announcement')->first()->membership($admin)->is_moderator);
    }

    public function test_hsts_header(): void
    {
        $this->get('https://flash.test/anmelden')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
