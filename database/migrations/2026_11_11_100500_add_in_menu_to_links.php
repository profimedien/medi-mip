<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Schnellzugriff im linken Menü: welche Weiterleitungen dort erscheinen, legt die Redaktion je Link fest
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('in_menu')->default(false)->after('description');
        });

        // Vorgabe Marco 11.11.2026: Time Card, DATEV, Benefits App, Givve Card (nicht medi-Website)
        DB::table('links')->whereIn('title', ['Time Card', 'DATEV Arbeitnehmer online', 'Benefits App', 'Givve Card'])->update(['in_menu' => true]);
    }

    public function down(): void
    {
        Schema::table('links', fn (Blueprint $table) => $table->dropColumn('in_menu'));
    }
};
