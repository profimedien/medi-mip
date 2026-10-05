<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\Forms\Schemas\FormForm;
use App\Filament\Resources\Forms\Tables\FormsTable;
use App\Filament\Support\ScopedContent;
use App\Models\Form;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FormResource extends Resource
{
    use ScopedContent;

    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Rückmeldung und Beteiligung';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Rückmeldung';

    protected static ?string $pluralModelLabel = 'Rückmeldungen';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ResponsesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
