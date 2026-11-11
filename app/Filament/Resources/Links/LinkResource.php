<?php

namespace App\Filament\Resources\Links;

use App\Filament\Resources\Links\Pages\ManageLinks;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\StaffAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Link;
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

class LinkResource extends Resource
{
    use StaffAdminOnly;


    protected static ?string $model = Link::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Weiterleitung';

    protected static ?string $pluralModelLabel = 'Weiterleitungen';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->label('Titel')->required(),
            TextInput::make('url')->label('Adresse')->required()
                ->rule('regex:~^(https?://\S+|#)$~')
                ->validationMessages(['regex' => 'Bitte eine Adresse mit https:// angeben oder # als Platzhalter.'])
                ->helperText('z. B. https://… oder # als Platzhalter'),
            TextInput::make('description')->label('Kurzbeschreibung'),
            Toggle::make('in_menu')->label('Im linken Menü unter Schnellzugriff zeigen')
                ->helperText('Die Box Schnellzugriff auf der Startseite zeigt weiterhin alle Weiterleitungen.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('title')->label('Titel')->weight('medium'),
                TextColumn::make('url')->label('Adresse')->limit(50),
                IconColumn::make('in_menu')->label('Im Menü')->boolean(),
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
        return ['index' => ManageLinks::route('/')];
    }
}
