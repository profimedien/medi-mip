<?php

namespace App\Filament\Support;

/** Ressource nur für Gesamt-Admins (Struktur, Einstellungen, Moderation) */
trait GesamtAdminOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isGesamtAdmin() ?? false;
    }
}
