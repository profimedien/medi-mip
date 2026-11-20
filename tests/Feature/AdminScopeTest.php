<?php

namespace Tests\Feature;

use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Support\Fields;
use App\Models\Contact;
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

    public function test_fachliche_leitung_pflegt_inhalte_aber_keine_mitarbeitenden(): void
    {
        $lead = $this->person('Fürth', ['Physiotherapie']);
        $lead->assignRole('fachliche-leitung');
        AdminScopeModel::create(['user_id' => $lead->id, 'department_id' => $this->department('Physiotherapie')->id]);
        $lead = $lead->fresh();

        $this->assertTrue($lead->isAdmin(), 'Zugang zur Redaktion');
        $this->assertFalse($lead->canManageStaff());
        $this->actingAs($lead)->get('/admin/users')->assertForbidden();
        $this->actingAs($lead)->get('/admin/posts')->assertOk();
        foreach (['/admin/links', '/admin/contacts', '/admin/faqs', '/admin/faq-categories', '/admin/onboarding-sections', '/admin/org-units', '/admin/organigramm'] as $path) {
            $this->actingAs($lead)->get($path)->assertForbidden();
        }

        $lead->syncLeadershipDepartments();
        $this->assertContains($this->department('Fachliche Leitungen')->id, $lead->fresh()->departmentIds());
    }

    public function test_ansprechpartner_nur_fuer_eigene_einrichtungen(): void
    {
        $admin = $this->person('Fürth', ['Rezeption']);
        $admin->assignRole('standort-admin');
        AdminScopeModel::create(['user_id' => $admin->id, 'location_id' => $this->location('Fürth')->id]);
        $fuerth = Contact::create(['type' => 'external', 'name' => 'Nur Fürth', 'responsibility' => 'IT']);
        $fuerth->locations()->sync([$this->location('Fürth')->id]);
        $both = Contact::create(['type' => 'external', 'name' => 'Fürth und Roth', 'responsibility' => 'IT']);
        $both->locations()->sync([$this->location('Fürth')->id, $this->location('Roth')->id]);
        $all = Contact::create(['type' => 'external', 'name' => 'Alle', 'responsibility' => 'Steuer']);

        $this->actingAs($admin->fresh());
        $this->assertSame([$fuerth->id], ContactResource::getEloquentQuery()->pluck('id')->all());
        $this->get('/admin/contacts')->assertOk();

        $deptAdmin = $this->person('Roth', ['Physiotherapie']);
        $deptAdmin->assignRole('abteilungs-admin');
        AdminScopeModel::create(['user_id' => $deptAdmin->id, 'department_id' => $this->department('Physiotherapie')->id]);
        $this->actingAs($deptAdmin->fresh())->get('/admin/contacts')->assertForbidden();
    }
}
