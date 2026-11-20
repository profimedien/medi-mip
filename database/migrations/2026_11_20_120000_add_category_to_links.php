<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Bereich je Weiterleitung: Schnellzugriff (Startseite/Menü) oder aushangpflichtige Gesetze (FAQ-Seite)
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('category')->default('schnellzugriff')->after('description')->index();
        });

        // medi (Christina Weber, Mail 20.11.2026): als Links statt Dateien, damit immer die aktuelle Fassung gilt
        foreach (\App\Models\Link::LAWS as $i => [$title, $url, $desc]) {
            DB::table('links')->updateOrInsert(
                ['title' => $title],
                ['url' => $url, 'description' => $desc, 'category' => 'gesetze', 'in_menu' => false, 'sort' => $i, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('links')->where('category', 'gesetze')->delete();
        Schema::table('links', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
