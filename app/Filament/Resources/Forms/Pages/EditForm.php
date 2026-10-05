<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Filament\Support\Fields;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Fields::guardAudience($data);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
