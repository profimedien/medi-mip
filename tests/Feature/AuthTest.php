<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private function invited(): User
    {
        return $this->person('Fürth', ['Physiotherapie'], [
            'username' => 'anna.test', 'email' => null, 'password' => null,
            'status' => 'invited', 'start_code' => 'ABCD-2345', 'must_change_password' => true,
        ]);
    }

    public function test_gaeste_werden_zur_anmeldung_geleitet(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_erstanmeldung_mit_startcode_fuehrt_zur_passwortvergabe(): void
    {
        $this->invited();
        $this->post(route('login'), ['login' => 'anna.test', 'password' => 'abcd2345'])
            ->assertRedirect(route('password.set'));
        $this->get('/news')->assertRedirect(route('password.set'));
    }

    public function test_passwort_festlegen_aktiviert_konto_und_entwertet_startcode(): void
    {
        $user = $this->invited();
        $this->actingAs($user)->post(route('password.store'), ['password' => 'neuesPasswort1', 'password_confirmation' => 'neuesPasswort1'])
            ->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNull($user->start_code);
        $this->assertFalse($user->must_change_password);

        auth()->logout();
        $this->post(route('login'), ['login' => 'anna.test', 'password' => 'ABCD-2345'])->assertSessionHasErrors('login');
        $this->post(route('login'), ['login' => 'anna.test', 'password' => 'neuesPasswort1'])->assertRedirect(route('home'));
    }

    public function test_falscher_code_und_gesperrtes_konto_werden_abgelehnt(): void
    {
        $user = $this->invited();
        $this->post(route('login'), ['login' => 'anna.test', 'password' => 'FALSCH-0000'])->assertSessionHasErrors('login');
        $user->update(['status' => 'disabled']);
        $this->post(route('login'), ['login' => 'anna.test', 'password' => 'ABCD-2345'])->assertSessionHasErrors('login');
    }

    public function test_neuer_startcode_setzt_passwort_zurueck(): void
    {
        $user = $this->person();
        $code = $user->resetAccess();
        $this->assertNull($user->fresh()->password);
        $this->assertSame($code, $user->fresh()->start_code);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code);
    }

    public function test_nur_admins_kommen_in_die_redaktion(): void
    {
        $this->actingAs($this->person())->get('/admin')->assertForbidden();
        $admin = $this->person();
        $admin->assignRole('standort-admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
