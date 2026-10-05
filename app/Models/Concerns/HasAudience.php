<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Zielgruppe eines Inhalts.
 *
 * Regel: Sichtbar, wenn
 *  - audience_all gesetzt ist, oder
 *  - die Person in audience_users steht, oder
 *  - Standorte UND/ODER Abteilungen passen: Sind beide gesetzt, müssen beide passen
 *    (z. B. "Physiotherapie in Fürth"), sonst genügt die gesetzte Liste.
 */
trait HasAudience
{
    public function initializeHasAudience(): void
    {
        $this->mergeCasts(['audience_all' => 'boolean']);
    }

    protected function audienceLocations(): Attribute
    {
        return $this->intList('audience_locations');
    }

    protected function audienceDepartments(): Attribute
    {
        return $this->intList('audience_departments');
    }

    protected function audienceUsers(): Attribute
    {
        return $this->intList('audience_users');
    }

    /** JSON-Liste immer als Integer speichern, damit JSON_CONTAINS zuverlässig greift */
    // bewusst ohne Rückgabetyp: sonst hält Laravel die Methode für einen Accessor "int_list"
    private function intList(string $column)
    {
        return Attribute::make(
            get: fn ($value) => array_values(array_map('intval', json_decode($value ?? '[]', true) ?: [])),
            set: fn ($value) => [$column => json_encode(array_values(array_unique(array_map('intval', array_filter((array) $value, fn ($v) => $v !== null && $v !== '')))))],
        );
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $locationId = $user->location_id;
        $departmentIds = $user->departmentIds();

        return $query->where(function (Builder $q) use ($user, $locationId, $departmentIds) {
            $q->where('audience_all', true)
                ->orWhereJsonContains('audience_users', $user->id)
                ->orWhere(function (Builder $q) use ($locationId, $departmentIds) {
                    // Standort passt, Abteilungen leer oder passend
                    $q->when($locationId, fn ($q) => $q->whereJsonContains('audience_locations', $locationId), fn ($q) => $q->whereRaw('1 = 0'))
                        ->where(function (Builder $q) use ($departmentIds) {
                            $q->whereNull('audience_departments')
                                ->orWhereJsonLength('audience_departments', 0)
                                ->orWhere(fn ($q) => $this->anyDepartment($q, $departmentIds));
                        });
                })
                ->orWhere(function (Builder $q) use ($departmentIds) {
                    // Abteilung passt, Standorte leer
                    $q->where(fn ($q) => $q->whereNull('audience_locations')->orWhereJsonLength('audience_locations', 0))
                        ->where(fn ($q) => $this->anyDepartment($q, $departmentIds));
                });
        });
    }

    private function anyDepartment(Builder $q, array $departmentIds): void
    {
        if ($departmentIds === []) {
            $q->whereRaw('1 = 0');

            return;
        }
        $q->where(function ($q) use ($departmentIds) {
            foreach ($departmentIds as $id) {
                $q->orWhereJsonContains('audience_departments', $id);
            }
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return static::query()->whereKey($this->getKey())->visibleTo($user)->exists();
    }

    /** Alle aktiven Personen der Zielgruppe (für Benachrichtigungen und Auswertungen) */
    public function audienceMembers(): Builder
    {
        $query = User::query()->where('status', '!=', 'disabled');
        if ($this->audience_all) {
            return $query;
        }

        $locations = $this->audience_locations;
        $departments = $this->audience_departments;

        return $query->where(function (Builder $q) use ($locations, $departments) {
            $q->whereIn('id', $this->audience_users ?: [0]);
            if ($locations || $departments) {
                $q->orWhere(function (Builder $q) use ($locations, $departments) {
                    if ($locations) {
                        $q->whereIn('location_id', $locations);
                    }
                    if ($departments) {
                        $q->whereHas('departments', fn ($d) => $d->whereIn('departments.id', $departments));
                    }
                });
            }
        });
    }

    /** Kurzbeschreibung für Listen, z. B. "Fürth · Physiotherapie" */
    public function audienceLabel(): string
    {
        if ($this->audience_all) {
            return 'Alle';
        }
        $parts = array_merge(
            \App\Models\Location::whereIn('id', $this->audience_locations)->pluck('short_name')->all(),
            \App\Models\Department::whereIn('id', $this->audience_departments)->pluck('name')->all(),
        );
        if ($this->audience_users) {
            $parts[] = count($this->audience_users).' Person(en)';
        }

        return $parts ? implode(' · ', $parts) : 'Niemand';
    }
}
