<?php

namespace App\Filament\Resources\Classifieds;

use App\Filament\Resources\Classifieds\Pages\ManageClassifieds;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Classified;
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

class ClassifiedResource extends Resource
{
    use GesamtAdminOnly;

    protected static ?string $model = Classified::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Rückmeldung und Beteiligung';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Kleinanzeige';

    protected static ?string $pluralModelLabel = 'Kleinanzeigen';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('topic')->label('Kategorie')->options(fn () => array_combine(\App\Models\Classified::topics(), \App\Models\Classified::topics())),
            TextInput::make('title')->label('Titel')->required(),
            Textarea::make('body')->label('Text')->required(),
            DateTimePicker::make('expires_at')->label('Läuft bis')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->weight('medium'),
                TextColumn::make('topic')->label('Kategorie')->badge()->color('info'),
                TextColumn::make('category')->label('Rubrik')->badge()->formatStateUsing(fn ($state) => \App\Models\Classified::CATEGORIES[$state] ?? $state),
                TextColumn::make('user.last_name')->label('Von')->formatStateUsing(fn ($record) => $record->user?->name),
                TextColumn::make('expires_at')->label('Läuft bis')->date('d.m.Y')->sortable()->color(fn ($state) => $state?->isPast() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('category')->label('Rubrik')->options(\App\Models\Classified::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageClassifieds::route('/')];
    }
}
