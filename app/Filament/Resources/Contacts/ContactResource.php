<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Contact;
use App\Models\Location;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Ansprechpartner';

    protected static ?string $pluralModelLabel = 'Ansprechpartner';

    /** Ansprechpartner hängen an Einrichtungen: Gesamt-Admin und Standort-Admins pflegen sie (Abstimmung 20.11.2026) */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['gesamt-admin', 'standort-admin']) ?? false;
    }

    /** Standort-Admins sehen nur Partner, die ausschließlich eigenen Einrichtungen zugeordnet sind */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (! $user || $user->isGesamtAdmin()) {
            return $query;
        }
        $own = $user->adminLocationIds() ?: [0];

        return $query->whereHas('locations', fn ($l) => $l->whereIn('locations.id', $own))
            ->whereDoesntHave('locations', fn ($l) => $l->whereNotIn('locations.id', $own));
    }

    /** Einrichtungen, die die angemeldete Person zuordnen darf */
    protected static function allowedLocationIds(): array
    {
        return auth()->user()?->adminLocationIds() ?? [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('type')->label('Art')->options(Contact::TYPES)->default('internal')->required()->selectablePlaceholder(false)->live(),
            Select::make('user_id')->label('Person aus den Mitarbeitenden')->searchable()
                ->getSearchResultsUsing(fn (string $search) => User::where('status', '!=', 'disabled')
                    ->where(fn ($q) => $q->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%"))
                    ->with('location')->limit(30)->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name.' · '.($u->location?->short_name ?? '')]))
                ->getOptionLabelUsing(fn ($value) => User::find($value)?->name)
                ->live()
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                    // Name und Kontaktdaten aus dem Profil übernehmen, sofern noch leer
                    if ($state && ($u = User::find($state))) {
                        $get('name') ?: $set('name', $u->name);
                        $get('phone') ?: $set('phone', $u->fullExtension() ?: $u->phone);
                        $get('email') ?: $set('email', $u->work_email ?: $u->email);
                        if (! $get('locations') && $u->location_id && in_array($u->location_id, static::allowedLocationIds())) {
                            $set('locations', [(string) $u->location_id]);
                        }
                    }
                })
                ->helperText('Name, Telefon und E-Mail werden übernommen, wenn die Felder noch leer sind.')
                ->visible(fn (Get $get) => $get('type') === 'internal'),
            TextInput::make('name')->label(fn (Get $get) => $get('type') === 'external' ? 'Firma oder Name' : 'Name')->required(),
            TextInput::make('contact_person')->label('Ansprechpartner bei der Firma')->placeholder('z. B. Frau Muster')
                ->visible(fn (Get $get) => $get('type') === 'external'),
            TextInput::make('responsibility')->label('Zuständigkeit')->required()->placeholder('z. B. Personalabteilung · Verträge, Urlaub'),
            Select::make('locations')->label('Einrichtungen')->multiple()->preload()
                ->relationship('locations', 'short_name', fn (Builder $query) => $query->whereIn('locations.id', static::allowedLocationIds()))
                // nur erlaubte Einrichtungen speichern, auch wenn jemand andere IDs mitschickt
                ->saveRelationshipsUsing(fn (Contact $record, ?array $state) => $record->locations()->sync(array_values(array_intersect(
                    array_map('intval', $state ?? []), static::allowedLocationIds()))))
                ->required(fn () => ! auth()->user()?->isGesamtAdmin())
                ->helperText(fn () => auth()->user()?->isGesamtAdmin() ? 'Leer lassen, wenn der Ansprechpartner für alle Einrichtungen gilt.' : null),
            TextInput::make('phone')->label('Telefon')->tel(),
            TextInput::make('email')->label('E-Mail')->email(),
            TextInput::make('availability')->label('Erreichbarkeit')->placeholder('z. B. Mo bis Do 8 bis 12 Uhr'),
            Fields::upload('photo', 'Foto', images: true),
            Textarea::make('description')->label('Wofür ist die Person zuständig?')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->weight('medium'),
                TextColumn::make('type')->label('Art')->badge()->formatStateUsing(fn ($state) => Contact::TYPES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'external' ? 'warning' : 'info'),
                TextColumn::make('responsibility')->label('Zuständigkeit')->wrap(),
                TextColumn::make('contact_person')->label('Ansprechpartner')->toggleable(),
                TextColumn::make('locations.short_name')->label('Einrichtungen')->badge()->placeholder('alle'),
                TextColumn::make('phone')->label('Telefon'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Art')->options(Contact::TYPES),
                SelectFilter::make('location')->label('Einrichtung')
                    ->options(fn () => Location::whereIn('id', static::allowedLocationIds())->orderBy('sort')->pluck('short_name', 'id'))
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->forLocation((int) $data['value']) : $query),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContacts::route('/')];
    }
}
