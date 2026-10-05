<?php

namespace App\Filament\Resources\OrgUnits\Pages;

use App\Filament\Resources\OrgUnits\OrgUnitResource;
use App\Filament\Support\Fields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrgUnits extends ManageRecords
{
    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
