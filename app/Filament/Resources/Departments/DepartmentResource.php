<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Department;
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

class DepartmentResource extends Resource
{
    use GesamtAdminOnly;

    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Personal und Struktur';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Abteilung';

    protected static ?string $pluralModelLabel = 'Abteilungen';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Name')->required()->unique(ignoreRecord: true),
            Textarea::make('description')->label('Beschreibung für die Abteilungsseite'),
            Select::make('locations')->label('Standorte')->relationship('locations', 'short_name')->multiple()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->weight('medium'),
                TextColumn::make('locations.short_name')->label('Standorte')->badge()->limitList(4),
                TextColumn::make('users_count')->counts('users')->label('Personen'),
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
        return ['index' => ManageDepartments::route('/')];
    }
}
