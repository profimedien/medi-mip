<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Kontrolle 11.11.2026: Violett gehört der Kategorie Fortbildung (S1),
    // bestehende Leitungen in die Leitungs-Abteilungen übernehmen (S4). Ergänzt nur, entfernt nichts.
    public function up(): void
    {
        DB::table('locations')->where('color', '#7c3aed')->update(['color' => '#9f1239']);

        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['fachliche-leitung', 'abteilungs-admin']))->get()->each->syncLeadershipDepartments();
    }

    public function down(): void
    {
        // keine Rücknahme: Farbe und Abteilungszuordnung sind Daten, die danach gepflegt werden
    }
};
