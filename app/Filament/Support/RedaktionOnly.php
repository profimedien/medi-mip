<?php

namespace App\Filament\Support;

/** Ressource für alle Redaktionsrollen, nicht für die reine Chat-Übersicht */
trait RedaktionOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
