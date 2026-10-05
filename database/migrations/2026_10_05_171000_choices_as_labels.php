<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Antwortmöglichkeiten von festen Schlüsseln auf frei wählbare Texte umstellen */
return new class extends Migration
{
    private const MAP = ['zusage' => 'Zusage', 'absage' => 'Absage', 'kenntnis' => 'Zur Kenntnis genommen'];

    public function up(): void
    {
        foreach (DB::table('forms')->whereNotNull('choices')->get(['id', 'choices']) as $form) {
            $labels = array_map(fn ($c) => self::MAP[$c] ?? $c, json_decode($form->choices, true) ?: []);
            DB::table('forms')->where('id', $form->id)->update(['choices' => json_encode(array_values($labels), JSON_UNESCAPED_UNICODE)]);
        }
        foreach (self::MAP as $key => $label) {
            DB::table('form_responses')->where('choice', $key)->update(['choice' => $label]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $key => $label) {
            DB::table('form_responses')->where('choice', $label)->update(['choice' => $key]);
        }
    }
};
