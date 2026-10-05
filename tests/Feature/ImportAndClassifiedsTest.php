<?php

namespace Tests\Feature;

use App\Models\Classified;
use App\Models\User;
use App\Services\UserImport;
use Tests\TestCase;

class ImportAndClassifiedsTest extends TestCase
{
    public function test_import_legt_personen_mit_startcode_an_und_aktualisiert_bekannte(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($csv, "Vorname;Nachname;Personalnummer;E-Mail;Standort;Abteilung;Funktion;Eintrittsdatum;Geburtstag\n"
            ."Eva;Muster;4711;;Fürth;Physiotherapie, Massage;Physiotherapeutin;01.03.2020;14.05.1990\n"
            ."Max;Unbekannt;4712;;Mars;Physiotherapie;;;\n");

        $report = (new UserImport)->run($csv, dryRun: false);
        $this->assertSame(1, $report['created']);
        $this->assertCount(1, $report['errors']);

        $eva = User::where('personnel_number', '4711')->first();
        $this->assertSame('eva.muster', $eva->username);
        $this->assertNotNull($eva->start_code);
        $this->assertSame(14, $eva->birthday_day);
        $this->assertSame(2, $eva->departments()->count());

        file_put_contents($csv, "Vorname;Nachname;Personalnummer;Standort;Funktion\nEva;Muster;4711;Fürth;Leitung\n");
        $report = (new UserImport)->run($csv);
        $this->assertSame(1, $report['updated']);
        $this->assertSame('Leitung', $eva->fresh()->job_title);
    }

    public function test_abgelaufene_kleinanzeigen_verschwinden(): void
    {
        $user = $this->person();
        Classified::create(['user_id' => $user->id, 'category' => 'private', 'title' => 'alt', 'body' => 'x', 'expires_at' => now()->subDay()]);
        Classified::create(['user_id' => $user->id, 'category' => 'private', 'title' => 'neu', 'body' => 'x', 'expires_at' => now()->addDay()]);
        $this->assertSame(['neu'], Classified::running()->pluck('title')->all());
    }
}
