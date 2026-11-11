<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Eigene Gruppen: Wer sie anlegt, moderiert sie und legt fest, ob auch alle Mitglieder Personen hinzufügen dürfen
    public function up(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->boolean('members_can_invite')->default(false)->after('read_only');
        });
    }

    public function down(): void
    {
        Schema::table('chat_groups', fn (Blueprint $table) => $table->dropColumn('members_can_invite'));
    }
};
