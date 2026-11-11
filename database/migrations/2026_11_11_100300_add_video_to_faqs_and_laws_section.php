<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FAQ: optional ein Video je Eintrag (Bilder über die Medienbibliothek). Onboarding: neuer Abschnitt Gesetze
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('answer');
        });

        if (! DB::table('onboarding_sections')->where('title', 'Gesetze')->exists()) {
            DB::table('onboarding_sections')->insert([
                'title' => 'Gesetze',
                'icon' => 'scale',
                'body' => '<p>Hier stehen bald die wichtigsten gesetzlichen Grundlagen für Ihre Arbeit bei medi, zum Beispiel Arbeitsschutz, Datenschutz und Hygiene.</p>',
                'sort' => (int) DB::table('onboarding_sections')->max('sort') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('faqs', fn (Blueprint $table) => $table->dropColumn('video_url'));
        // Abschnitt Gesetze bleibt bewusst stehen, er kann inzwischen Inhalte haben
    }
};
