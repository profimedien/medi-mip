<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Event;
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

class EventResource extends Resource
{
    use ScopedContent;

    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Termin';

    protected static ?string $pluralModelLabel = 'Termine';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make([
                TextInput::make('title')->label('Titel')->required(),
                Textarea::make('description')->label('Beschreibung')->rows(3),
                TextInput::make('place')->label('Ort'),
                DateTimePicker::make('starts_at')->label('Beginn')->required()->seconds(false),
                DateTimePicker::make('ends_at')->label('Ende')->seconds(false),
                Toggle::make('all_day')->label('Ganztägig'),
            ])->columnSpan(2),
            Fields::audience()->columnSpan(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->weight('medium'),
                TextColumn::make('starts_at')->label('Beginn')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('place')->label('Ort'),
                TextColumn::make('audience')->label('Zielgruppe')->state(fn ($record) => $record->audienceLabel())->badge()->color('info'),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data) => Fields::guardAudience($data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEvents::route('/')];
    }
}
