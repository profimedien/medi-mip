<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    // Chat-Punkte medi 20.11.2026: Speicherdauer je Gruppe, Chatregeln gelesen, Rolle Chat-Übersicht,
    // Nachrichtentext verschlüsselt speichern (vorhandene Nachrichten werden umgeschlüsselt)
    public function up(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            // leer = globale Einstellung, 0 = dauerhaft speichern, sonst Tage
            $table->unsignedInteger('retention_days')->nullable()->after('history_for_new_members');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('chat_rules_seen_at')->nullable();
        });

        Role::findOrCreate('chat-uebersicht', 'web');

        DB::table('chat_messages')->whereNotNull('body')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                if (! $this->isEncrypted($row->body)) {
                    DB::table('chat_messages')->where('id', $row->id)->update(['body' => Crypt::encryptString($row->body)]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('chat_messages')->whereNotNull('body')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                if ($this->isEncrypted($row->body)) {
                    DB::table('chat_messages')->where('id', $row->id)->update(['body' => Crypt::decryptString($row->body)]);
                }
            }
        });
        Schema::table('chat_groups', fn (Blueprint $table) => $table->dropColumn('retention_days'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('chat_rules_seen_at'));
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
