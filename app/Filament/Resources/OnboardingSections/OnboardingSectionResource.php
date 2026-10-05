<?php

namespace App\Filament\Resources\OnboardingSections;

use App\Filament\Resources\OnboardingSections\Pages\ManageOnboardingSections;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\OnboardingSection;
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

class OnboardingSectionResource extends Resource
{

    protected static ?string $model = OnboardingSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Onboarding-Abschnitt';

    protected static ?string $pluralModelLabel = 'Onboarding';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->label('Titel')->required(),
            Select::make('icon')->label('Symbol')->options(['clock' => 'Uhr', 'heart' => 'Herz', 'sun' => 'Sonne', 'academic-cap' => 'Fortbildung', 'gift' => 'Geschenk', 'bolt' => 'Rad/Energie', 'document-text' => 'Dokument', 'currency-euro' => 'Geld', 'shield-check' => 'Sicherheit']),
            RichEditor::make('body')->label('Inhalt')->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList']]),
            TextInput::make('video_url')->label('Erklärvideo (YouTube- oder Vimeo-Link)')->url(),
            Fields::upload('documents', 'Formulare und Dokumente', multiple: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('title')->label('Titel')->weight('medium'),
                IconColumn::make('video_url')->label('Video')->boolean(fn ($state) => filled($state))->state(fn ($record) => filled($record->video_url)),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()->modalWidth('4xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOnboardingSections::route('/')];
    }
}
