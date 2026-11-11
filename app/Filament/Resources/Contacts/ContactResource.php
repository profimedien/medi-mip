<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Contact;
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
use UnitEnum;

class ContactResource extends Resource
{

    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Ansprechpartner';

    protected static ?string $pluralModelLabel = 'Ansprechpartner';

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
                        $get('location_id') ?: $set('location_id', $u->location_id);
                    }
                })
                ->helperText('Name, Telefon und E-Mail werden übernommen, wenn die Felder noch leer sind.')
                ->visible(fn (Get $get) => $get('type') === 'internal'),
            TextInput::make('name')->label('Name')->required(),
            TextInput::make('responsibility')->label('Zuständigkeit')->required()->placeholder('z. B. Personalabteilung · Verträge, Urlaub'),
            Select::make('location_id')->label('Standort')->relationship('location', 'short_name')->placeholder('standortübergreifend'),
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
                TextColumn::make('location.short_name')->label('Standort')->placeholder('alle'),
                TextColumn::make('phone')->label('Telefon'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Art')->options(Contact::TYPES),
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
