<?php

namespace App\Filament\Resources\FaqQuestions\Pages;

use App\Filament\Resources\FaqQuestions\FaqQuestionResource;
use App\Filament\Support\Fields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqQuestions extends ManageRecords
{
    protected static string $resource = FaqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
