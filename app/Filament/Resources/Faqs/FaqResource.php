<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\Faq;
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

class FaqResource extends Resource
{

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'FAQ-Eintrag';

    protected static ?string $pluralModelLabel = 'FAQ';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('faq_category_id')->label('Kategorie')->relationship('category', 'name')->required()->createOptionForm([TextInput::make('name')->label('Name')->required()]),
            TextInput::make('question')->label('Frage')->required(),
            RichEditor::make('answer')->label('Antwort')->required()->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList']]),
            Toggle::make('published')->label('Veröffentlicht')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('question')->label('Frage')->searchable()->wrap()->weight('medium'),
                TextColumn::make('category.name')->label('Kategorie')->badge(),
                IconColumn::make('published')->label('Sichtbar')->boolean(),
            ])
            ->filters([
                SelectFilter::make('faq_category_id')->label('Kategorie')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqs::route('/')];
    }
}
