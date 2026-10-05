<?php

namespace App\Filament\Resources\FaqQuestions;

use App\Filament\Resources\FaqQuestions\Pages\ManageFaqQuestions;
use App\Filament\Support\Fields;
use App\Filament\Support\GesamtAdminOnly;
use App\Filament\Support\ScopedContent;
use App\Models\FaqQuestion;
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

class FaqQuestionResource extends Resource
{

    protected static ?string $model = FaqQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'eingereichte Frage';

    protected static ?string $pluralModelLabel = 'Eingereichte Fragen';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('question')->label('Frage')->disabled(),
            Select::make('status')->label('Status')->options(['open' => 'Offen', 'answered' => 'Beantwortet', 'rejected' => 'Abgelehnt'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('question')->label('Frage')->wrap()->searchable(),
                TextColumn::make('user.last_name')->label('Von')->formatStateUsing(fn ($record) => $record->user?->name),
                TextColumn::make('created_at')->label('Am')->date('d.m.Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => ['open' => 'Offen', 'answered' => 'Beantwortet', 'rejected' => 'Abgelehnt'][$state] ?? $state)->color(fn ($state) => ['open' => 'danger', 'answered' => 'success'][$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Offen', 'answered' => 'Beantwortet', 'rejected' => 'Abgelehnt'])->default('open'),
            ])
            ->recordActions([
                Action::make('toFaq')->label('In FAQ übernehmen')->icon('heroicon-o-plus-circle')
                    ->visible(fn ($record) => $record->status === 'open')
                    ->schema([
                        Select::make('faq_category_id')->label('Kategorie')->options(\App\Models\FaqCategory::orderBy('sort')->pluck('name', 'id'))->required(),
                        TextInput::make('question')->label('Frage')->required(),
                        RichEditor::make('answer')->label('Antwort')->required(),
                    ])
                    ->fillForm(fn ($record) => ['question' => $record->question])
                    ->action(function ($record, array $data) {
                        $faq = \App\Models\Faq::create($data);
                        $record->update(['status' => 'answered', 'faq_id' => $faq->id]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqQuestions::route('/')];
    }
}
