<?php

namespace App\Filament\Support;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Support\AdminScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/** Wiederkehrende Formularbausteine der Redaktion */
class Fields
{
    /**
     * Zielgruppe: alle, Standorte, Abteilungen, Einzelpersonen.
     * Standort- und Abteilungs-Admins sehen nur ihren Bereich und dürfen nicht an "alle" senden.
     */
    public static function audience(): Section
    {
        $admin = auth()->user();
        $gesamt = $admin?->isGesamtAdmin() ?? false;

        return Section::make('Zielgruppe')
            ->description('Wer soll den Inhalt sehen? Sind Standorte und Abteilungen gewählt, müssen beide passen (z. B. Physiotherapie in Fürth). Einzelpersonen sehen ihn immer.')
            ->schema([
                Toggle::make('audience_all')
                    ->label('Für alle Mitarbeitenden')
                    ->default($gesamt)
                    ->live()
                    ->visible($gesamt),
                Select::make('audience_locations')
                    ->label('Standorte')
                    ->multiple()
                    ->options(fn () => Location::whereIn('id', $admin->adminLocationIds())->orderBy('sort')->pluck('short_name', 'id'))
                    ->hidden(fn (Get $get) => $gesamt && $get('audience_all')),
                Select::make('audience_departments')
                    ->label('Abteilungen')
                    ->multiple()
                    ->options(fn () => Department::whereIn('id', $admin->adminDepartmentIds() ?: Department::pluck('id')->all())->orderBy('name')->pluck('name', 'id'))
                    ->hidden(fn (Get $get) => $gesamt && $get('audience_all')),
                Select::make('audience_users')
                    ->label('Einzelpersonen')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => AdminScope::users($admin)
                        ->where(fn ($q) => $q->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%"))
                        ->limit(30)->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name.' · '.($u->location?->short_name ?? '')]))
                    ->getOptionLabelsUsing(fn (array $values) => User::whereIn('id', $values)->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name]))
                    ->hidden(fn (Get $get) => $gesamt && $get('audience_all')),
            ])->columns(1);
    }

    /** Für Standort-/Abteilungs-Admins: Zielgruppe absichern, bevor gespeichert wird */
    public static function guardAudience(array $data): array
    {
        $admin = auth()->user();
        if (! $admin->isGesamtAdmin()) {
            $data['audience_all'] = false;
            $data['audience_locations'] = array_values(array_intersect(array_map('intval', $data['audience_locations'] ?? []), $admin->adminLocationIds()));
            $data['audience_departments'] = array_map('intval', $data['audience_departments'] ?? []);
            if (! $admin->adminLocationIds()) {
                // Abteilungs-Admin: nur eigene Abteilungen
                $data['audience_departments'] = array_values(array_intersect($data['audience_departments'], $admin->adminDepartmentIds()));
            }
            if (! $data['audience_locations'] && ! $data['audience_departments'] && empty($data['audience_users'])) {
                // ohne Auswahl: der ganze eigene Bereich
                $data['audience_locations'] = $admin->adminLocationIds();
                $data['audience_departments'] = $admin->adminLocationIds() ? [] : $admin->adminDepartmentIds();
            }
        } elseif (! empty($data['audience_all'])) {
            $data['audience_locations'] = $data['audience_departments'] = $data['audience_users'] = [];
        }

        return $data;
    }

    public static function upload(string $collection, string $label, bool $multiple = false, bool $images = false): SpatieMediaLibraryFileUpload
    {
        $field = SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->visibility('private')
            ->maxSize(20480)
            ->downloadable()
            ->openable();
        if ($multiple) {
            $field->multiple()->reorderable()->appendFiles();
        }
        if ($images) {
            $field->image()->imageEditor();
        }

        return $field;
    }
}
