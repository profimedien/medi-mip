<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $admin = auth()->user();
        $gesamt = $admin->isGesamtAdmin();

        return $schema->columns(3)->components([
            Group::make([
                Section::make('Person')->columns(2)->schema([
                    TextInput::make('first_name')->label('Vorname')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && $state && $get('last_name')
                            ? $set('username', User::makeUsername($state, $get('last_name'))) : null),
                    TextInput::make('last_name')->label('Nachname')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && $state && $get('first_name')
                            ? $set('username', User::makeUsername($get('first_name'), $state)) : null),
                    TextInput::make('username')->label('Benutzername')->required()->unique(ignoreRecord: true)
                        ->regex('/^[a-z0-9.]+$/')->helperText('Kleinbuchstaben, Ziffern und Punkt. Wird für die Anmeldung genutzt.'),
                    TextInput::make('email')->label('E-Mail (optional)')->email()->unique(ignoreRecord: true)
                        ->helperText('Ohne E-Mail erfolgt die Anmeldung per Benutzername und Startcode.'),
                    TextInput::make('job_title')->label('Funktion'),
                    TextInput::make('personnel_number')->label('Personalnummer')->helperText('Nur intern sichtbar.'),
                    DatePicker::make('entry_date')->label('Eintrittsdatum')->native(false)->displayFormat('d.m.Y'),
                    Group::make([
                        TextInput::make('birthday_day')->label('Geburtstag Tag')->numeric()->minValue(1)->maxValue(31),
                        TextInput::make('birthday_month')->label('Monat')->numeric()->minValue(1)->maxValue(12),
                    ])->columns(2),
                ]),
                Section::make('Zuordnung')->columns(2)->schema([
                    Select::make('location_id')->label('Hauptstandort')->required()
                        ->options(fn () => Location::whereIn('id', $admin->adminLocationIds() ?: Location::pluck('id'))->orderBy('sort')->pluck('name', 'id')),
                    Select::make('departments')->label('Abteilungen')->multiple()->preload()
                        ->relationship('departments', 'name')
                        ->helperText('Mehrfachauswahl, z. B. für Springer oder Leitungen.'),
                ]),
            ])->columnSpan(2),
            Group::make([
                Section::make('Zugang')->schema([
                    Select::make('status')->label('Status')->options([
                        'invited' => 'Eingeladen (Startcode offen)',
                        'active' => 'Aktiv',
                        'disabled' => 'Gesperrt',
                    ])->default('invited')->required()->disabledOn('create'),
                    Select::make('roles')->label('Rolle')->relationship('roles', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => ['gesamt-admin' => 'Gesamt-Admin', 'standort-admin' => 'Standort-Admin', 'abteilungs-admin' => 'Abteilungs-Admin', 'mitarbeiter' => 'Mitarbeiter/in'][$record->name] ?? $record->name)
                        ->preload()->visible($gesamt),
                    Repeater::make('adminScopes')->label('Zuständigkeit als Admin')->relationship()->visible($gesamt)
                        ->addActionLabel('Bereich hinzufügen')->defaultItems(0)
                        ->schema([
                            Select::make('location_id')->label('Standort')->options(Location::orderBy('sort')->pluck('short_name', 'id')),
                            Select::make('department_id')->label('oder Abteilung')->options(Department::orderBy('name')->pluck('name', 'id')),
                        ])
                        ->helperText('Nur für Standort- und Abteilungs-Admins.'),
                ]),
                Section::make('Profil')->schema([
                    Toggle::make('profile_visible')->label('Im Verzeichnis sichtbar'),
                    Toggle::make('show_birthday')->label('Geburtstag im Kalender'),
                    Toggle::make('show_anniversary')->label('Jubiläum im Kalender')->default(true),
                ])->description('Die Person kann das im eigenen Profil selbst ändern.')->collapsed(),
            ])->columnSpan(1),
        ]);
    }
}
