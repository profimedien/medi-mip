<?php

namespace App\Filament\Resources\OnboardingSections\Pages;

use App\Filament\Resources\OnboardingSections\OnboardingSectionResource;
use App\Filament\Support\Fields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOnboardingSections extends ManageRecords
{
    protected static string $resource = OnboardingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->modalWidth('4xl')];
    }
}
