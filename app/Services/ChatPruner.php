<?php

namespace App\Services;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Cache;

/**
 * Löscht Chat-Nachrichten samt Anhängen endgültig, sobald sie älter als die Speicherdauer der Gruppe sind.
 * Speicherdauer: Gruppenwert (retention_days), sonst Einstellung chat_retention_days (Standard 365), 0 = dauerhaft.
 */
class ChatPruner
{
    /** Gelöschte Nachrichten */
    public function run(): int
    {
        $deleted = 0;
        foreach (ChatGroup::all() as $group) {
            $days = $group->effectiveRetentionDays();
            if (! $days) {
                continue;
            }
            ChatMessage::where('chat_group_id', $group->id)->where('created_at', '<', now()->subDays($days))
                ->chunkById(200, function ($messages) use (&$deleted) {
                    foreach ($messages as $message) {
                        $message->delete(); // Medienbibliothek entfernt die Dateien mit
                        $deleted++;
                    }
                });
        }

        return $deleted;
    }

    /**
     * Ohne Cronjob (Testsystem): beim Aufruf des Chats höchstens einmal am Tag auslösen.
     * Cache::add setzt die Sperre nur, wenn sie noch nicht existiert.
     */
    public static function runIfDue(): void
    {
        if (Cache::add('chat-prune-ran', now()->toDateTimeString(), now()->addDay())) {
            app(self::class)->run();
        }
    }
}
