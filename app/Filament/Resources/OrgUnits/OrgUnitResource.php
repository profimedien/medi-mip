<?php

namespace App\Filament\Resources\OrgUnits;

use App\Filament\Resources\OrgUnits\Pages\ManageOrgUnits;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\OrgUnit;
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

class OrgUnitResource extends Resource
{

    protected static ?string $model = OrgUnit::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Organigramm-Einheit';

    protected static ?string $pluralModelLabel = 'Organigramm';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Bezeichnung')->required(),
            Select::make('parent_id')->label('Übergeordnet')->relationship('parent', 'name')->placeholder('oberste Ebene'),
            Select::make('head_user_id')->label('Leitung (Person im Portal)')->relationship('head', 'last_name')->getOptionLabelFromRecordUsing(fn ($r) => $r->name)->searchable(['first_name', 'last_name']),
            TextInput::make('head_name')->label('oder Leitung als Text'),
            Textarea::make('description')->label('Beschreibung')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Bezeichnung')->weight('medium'),
                TextColumn::make('parent.name')->label('Übergeordnet')->placeholder('oberste Ebene'),
                TextColumn::make('head_label')->label('Leitung'),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrgUnits::route('/')];
    }
}
