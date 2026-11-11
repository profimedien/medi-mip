<?php

namespace Tests\Feature;

use App\Livewire\Contacts;
use App\Models\Contact;
use Livewire\Livewire;
use Tests\TestCase;

class DirectoryContactsTest extends TestCase
{
    public function test_durchwahl_mit_voller_standortnummer(): void
    {
        $roth = $this->location('Roth');
        $roth->update(['phone' => '09171 1234-0']);
        $user = $this->person('Roth', ['Physiotherapie'], ['phone_extension' => '219']);
        $this->assertSame('09171 1234-219', $user->fresh()->fullExtension());

        $roth->update(['phone' => null]);
        $this->assertSame('219', $user->fresh()->fullExtension(), 'ohne Standortnummer bleibt die Durchwahl');
    }

    public function test_ansprechpartner_filter_intern_extern(): void
    {
        $colleague = $this->person();
        Contact::create(['type' => 'internal', 'user_id' => $colleague->id, 'name' => 'Petra Intern', 'responsibility' => 'Personal']);
        $external = Contact::create(['type' => 'external', 'user_id' => $colleague->id, 'name' => 'EDV Schmidt', 'responsibility' => 'IT']);
        $this->assertNull($external->fresh()->user_id, 'extern hängt an keiner Person');

        Livewire::actingAs($colleague)->test(Contacts::class)->assertSee('Petra Intern')->assertSee('EDV Schmidt')
            ->set('type', 'external')->assertSee('EDV Schmidt')->assertDontSee('Petra Intern');
    }
}
