<?php

namespace App\Filament\Support;

/** Globaler Wissensbereich: Gesamt-, Standort- und Abteilungs-Admins, nicht fachliche Leitungen */
trait StaffAdminOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->canManageStaff() ?? false;
    }
}
