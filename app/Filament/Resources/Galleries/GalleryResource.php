<?php

namespace App\Filament\Resources\Galleries;

use App\Filament\Resources\Galleries\Pages\ManageGalleries;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Gallery;
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

class GalleryResource extends Resource
{
    use ScopedContent;

    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Galerie';

    protected static ?string $pluralModelLabel = 'Galerien';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make([
                TextInput::make('title')->label('Titel')->required(),
                Textarea::make('description')->label('Beschreibung')->rows(2),
                DatePicker::make('event_date')->label('Datum')->native(false)->displayFormat('d.m.Y'),
                Toggle::make('published')->label('Veröffentlicht')->default(true),
                Fields::upload('photos', 'Fotos', multiple: true, images: true)->panelLayout('grid'),
            ])->columnSpan(2),
            Fields::audience()->columnSpan(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_date', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->weight('medium'),
                TextColumn::make('event_date')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('media_count')->counts('media')->label('Fotos'),
                IconColumn::make('published')->label('Sichtbar')->boolean(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data) => Fields::guardAudience($data))->modalWidth('6xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGalleries::route('/')];
    }
}
