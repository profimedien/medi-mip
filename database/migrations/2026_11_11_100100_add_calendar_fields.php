<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kalender (Feedback 11.11.2026): Kategorie Fortbildung, Farbe je Standort, gemerkte Filter je Person
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->default('event')->after('title'); // event, training
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('phone');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->json('calendar_filter')->nullable()->after('notification_settings');
        });

        // Startfarben für vorhandene Standorte, nur wo noch keine Farbe gesetzt ist
        $palette = ['#2563eb', '#9f1239', '#db2777', '#0891b2', '#dc2626', '#1e3a8a', '#92400e', '#c026d3', '#475569', '#ca8a04']; // wie Location::COLORS
        foreach (DB::table('locations')->whereNull('color')->orderBy('sort')->orderBy('id')->pluck('id')->values() as $i => $id) {
            DB::table('locations')->where('id', $id)->update(['color' => $palette[$i % count($palette)]]);
        }
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::table('locations', fn (Blueprint $table) => $table->dropColumn('color'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('calendar_filter'));
    }
};
