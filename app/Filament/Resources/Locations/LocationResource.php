<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Location;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    use GesamtAdminOnly;

    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Personal und Struktur';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Standort';

    protected static ?string $pluralModelLabel = 'Standorte';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Name')->required()->columnSpanFull(),
            TextInput::make('short_name')->label('Kurzname')->required()->helperText('Für Listen und Chatgruppen'),
            TextInput::make('phone')->label('Telefon'),
            TextInput::make('street')->label('Straße'),
            TextInput::make('zip')->label('PLZ'),
            TextInput::make('city')->label('Ort'),
            Toggle::make('active')->label('Aktiv')->default(true)->inline(false),
            Select::make('departments')->label('Abteilungen an diesem Standort')->relationship('departments', 'name')->multiple()->preload()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('short_name')->label('Kurzname')->weight('medium'),
                TextColumn::make('name')->label('Name')->wrap(),
                TextColumn::make('departments.name')->label('Abteilungen')->badge()->limitList(4),
                TextColumn::make('users_count')->counts('users')->label('Personen'),
                IconColumn::make('active')->label('Aktiv')->boolean(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()->after(fn () => app(\App\Services\ChatGroupSync::class)->run()),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
