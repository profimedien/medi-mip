<?php

namespace App\Filament\Resources\Classifieds\Pages;

use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Support\Fields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageClassifieds extends ManageRecords
{
    protected static string $resource = ClassifiedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
