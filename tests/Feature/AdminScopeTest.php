<?php

namespace Tests\Feature;

use App\Filament\Support\Fields;
use App\Models\AdminScope as AdminScopeModel;
use App\Support\AdminScope;
use Tests\TestCase;

class AdminScopeTest extends TestCase
{
    public function test_standort_admin_sieht_nur_eigenen_standort(): void
    {
        $admin = $this->person('Fürth', ['Rezeption']);
        $admin->assignRole('standort-admin');
        AdminScopeModel::create(['user_id' => $admin->id, 'location_id' => $this->location('Fürth')->id]);
        $inside = $this->person('Fürth');
        $outside = $this->person('Roth');

        $ids = AdminScope::users($admin->fresh())->pluck('id')->all();
        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_bereichs_admin_kann_nicht_an_alle_senden(): void
    {
        $admin = $this->person('Fürth', ['Rezeption']);
        $admin->assignRole('standort-admin');
        AdminScopeModel::create(['user_id' => $admin->id, 'location_id' => $this->location('Fürth')->id]);
        $this->actingAs($admin->fresh());

        $data = Fields::guardAudience(['audience_all' => true, 'audience_locations' => [$this->location('Roth')->id]]);
        $this->assertFalse($data['audience_all']);
        $this->assertSame([$this->location('Fürth')->id], $data['audience_locations'], 'fremder Standort entfernt, eigener Bereich gesetzt');
    }
}
