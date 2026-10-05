<?php

namespace App\Filament\Resources\MaterialRequests;

use App\Filament\Resources\MaterialRequests\Pages\ManageMaterialRequests;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\MaterialRequest;
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

class MaterialRequestResource extends Resource
{
    use GesamtAdminOnly;

    protected static ?string $model = MaterialRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Rückmeldung und Beteiligung';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Materialanfrage';

    protected static ?string $pluralModelLabel = 'Materialanfragen';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('note')->label('Anmerkung')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('item')->label('Material')->wrap()->searchable()->weight('medium'),
                TextColumn::make('quantity')->label('Menge'),
                TextColumn::make('location.short_name')->label('Standort'),
                TextColumn::make('needed_by')->label('Bis')->date('d.m.Y'),
                TextColumn::make('user.last_name')->label('Von')->formatStateUsing(fn ($record) => $record->user?->name),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => ['new' => 'Neu', 'in_progress' => 'In Arbeit', 'done' => 'Erledigt'][$state] ?? $state)->color(fn ($state) => ['new' => 'danger', 'in_progress' => 'warning', 'done' => 'success'][$state] ?? 'gray'),
            ])
            ->filters([

            ])
            ->recordActions([
                Action::make('status')->label('Status')->icon('heroicon-o-check')->schema([Select::make('status')->label('Status')->options(['new' => 'Neu', 'in_progress' => 'In Arbeit', 'done' => 'Erledigt'])->required()])->fillForm(fn ($record) => ['status' => $record->status])->action(fn ($record, array $data) => $record->update($data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMaterialRequests::route('/')];
    }
}
