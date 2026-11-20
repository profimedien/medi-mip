<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ansprechpartner und Kooperationspartner je Einrichtung (Wunsch medi 20.11.2026).
 * Ein Partner kann für mehrere Einrichtungen gelten, keine Zuordnung = alle.
 * Die bisherige Einzelspalte location_id wandert in die Zuordnungstabelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_location', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_id', 'location_id']);
        });

        DB::table('contacts')->whereNotNull('location_id')->orderBy('id')->each(function ($c) {
            DB::table('contact_location')->insert(['contact_id' => $c->id, 'location_id' => $c->location_id]);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('name'); // Ansprechpartner bei einer Firma
            $table->dropConstrainedForeignId('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('responsibility')->constrained()->nullOnDelete();
            $table->dropColumn('contact_person');
        });

        foreach (DB::table('contact_location')->get()->groupBy('contact_id') as $contactId => $rows) {
            DB::table('contacts')->where('id', $contactId)->update(['location_id' => $rows->first()->location_id]);
        }

        Schema::dropIfExists('contact_location');
    }
};
