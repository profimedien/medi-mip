<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Eigene Gruppen: Moderation legt fest, ob nachträglich Hinzugefügte den bisherigen Verlauf sehen.
    // visible_from_message_id: Nachrichten bis einschließlich dieser ID bleiben für das Mitglied ausgeblendet (0 = ganzer Verlauf).
    public function up(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->boolean('history_for_new_members')->default(true)->after('members_can_invite');
        });
        Schema::table('chat_members', function (Blueprint $table) {
            $table->unsignedBigInteger('visible_from_message_id')->default(0)->after('last_read_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_groups', fn (Blueprint $table) => $table->dropColumn('history_for_new_members'));
        Schema::table('chat_members', fn (Blueprint $table) => $table->dropColumn('visible_from_message_id'));
    }
};
