<?php

namespace App\Filament\Resources\Suggestions;

use App\Filament\Resources\Suggestions\Pages\ManageSuggestions;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Suggestion;
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

class SuggestionResource extends Resource
{
    use GesamtAdminOnly;

    protected static ?string $model = Suggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Rückmeldung und Beteiligung';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Verbesserungsvorschlag';

    protected static ?string $pluralModelLabel = 'Verbesserungsvorschläge';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('body')->label('Vorschlag')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('topic')->label('Thema')->badge(),
                TextColumn::make('body')->label('Vorschlag')->wrap()->limit(160)->searchable(),
                TextColumn::make('user.last_name')->label('Von')->formatStateUsing(fn ($record) => $record->user?->name),
                TextColumn::make('created_at')->label('Am')->date('d.m.Y')->sortable(),
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
        return ['index' => ManageSuggestions::route('/')];
    }
}
