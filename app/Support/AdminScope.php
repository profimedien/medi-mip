<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Was darf ein Admin sehen und bearbeiten?
 * Gesamt-Admin: alles. Standort-Admin: eigene Standorte. Abteilungs-Admin: eigene Abteilungen.
 */
class AdminScope
{
    /** Personen im Zuständigkeitsbereich */
    public static function users(User $admin): Builder
    {
        $query = User::query();
        if ($admin->isGesamtAdmin()) {
            return $query;
        }

        $locations = $admin->adminLocationIds();
        $departments = $admin->adminDepartmentIds();

        return $query->where(function (Builder $q) use ($locations, $departments) {
            $q->whereIn('location_id', $locations ?: [0])
                ->orWhereHas('departments', fn ($d) => $d->whereIn('departments.id', $departments ?: [0]));
        });
    }

    /**
     * Inhalte mit Zielgruppe: eigene Beiträge oder solche, die vollständig im eigenen Bereich liegen.
     * Inhalte für "alle" verwaltet nur der Gesamt-Admin (oder wer sie angelegt hat).
     */
    public static function content(Builder $query, User $admin, string $authorColumn = 'author_id'): Builder
    {
        if ($admin->isGesamtAdmin()) {
            return $query;
        }

        $locations = $admin->adminLocationIds();
        $departments = $admin->adminDepartmentIds();

        return $query->where(function (Builder $q) use ($admin, $locations, $departments, $authorColumn) {
            $q->where($authorColumn, $admin->id)
                ->orWhere(function (Builder $q) use ($locations, $departments) {
                    $q->where('audience_all', false)->where(function (Builder $q) use ($locations, $departments) {
                        foreach ($locations as $id) {
                            $q->orWhereJsonContains('audience_locations', $id);
                        }
                        foreach ($departments as $id) {
                            $q->orWhereJsonContains('audience_departments', $id);
                        }
                    });
                });
        });
    }
}
