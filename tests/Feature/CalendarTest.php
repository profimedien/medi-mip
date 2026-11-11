<?php

namespace Tests\Feature;

use App\Livewire\Calendar;
use App\Models\Event;
use App\Services\CalendarFeed;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    public function test_fortbildung_ist_eigene_kategorie_mit_standortfarbe(): void
    {
        $user = $this->person('Fürth');
        $fuerth = $this->location('Fürth');
        Event::create(['title' => 'Kinesio-Taping', 'category' => 'training', 'starts_at' => now()->setTime(10, 0),
            'audience_all' => false, 'audience_locations' => [$fuerth->id]]);

        $entry = app(CalendarFeed::class)->between($user, CarbonImmutable::today(), CarbonImmutable::today())->firstWhere('title', 'Kinesio-Taping');
        $this->assertSame('training', $entry['type']);
        $this->assertSame($fuerth->id, $entry['location_id']);
    }

    public function test_filter_wird_je_person_gemerkt(): void
    {
        $user = $this->person('Fürth');
        Livewire::actingAs($user)->test(Calendar::class)->set('location', 'all')->set('types', ['training']);
        $this->assertSame(['location' => 'all', 'types' => ['training']], $user->fresh()->calendar_filter);

        Livewire::actingAs($user->fresh())->test(Calendar::class)->assertSet('location', 'all')->assertSet('types', ['training']);
    }
}
