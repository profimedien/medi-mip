<?php

namespace Tests\Feature;

use App\Models\Link;
use Tests\TestCase;

class LawLinksTest extends TestCase
{
    public function test_migration_legt_die_fuenf_gesetze_an(): void
    {
        $this->assertSame(5, Link::category('gesetze')->count());
        $this->assertSame(0, Link::category('gesetze')->where('in_menu', true)->count());
    }

    public function test_gesetze_auf_faq_seite_nicht_auf_startseite(): void
    {
        Link::create(['title' => 'Time Card', 'url' => 'https://medi.timecard365.de/', 'in_menu' => true]);
        $user = $this->person();

        $this->actingAs($user)->get(route('faq'))->assertOk()
            ->assertSee('Aushangpflichtige Gesetze')
            ->assertSee('https://www.gesetze-im-internet.de/arbzg/', false);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('Time Card')
            ->assertDontSee('gesetze-im-internet.de', false);
    }
}
