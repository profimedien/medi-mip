<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    // Feedback medi 11.11.2026: Rolle fachliche Leitung zusätzlich zur Abteilungsleitung
    public function up(): void
    {
        Role::findOrCreate('fachliche-leitung', 'web');
    }

    public function down(): void
    {
        // Rolle bleibt bestehen, Zuordnungen an Personen sollen nicht verloren gehen
    }
};
