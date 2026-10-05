<?php

namespace App\Filament\Resources\ChatGroups\Pages;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use App\Models\ChatGroup;
use App\Services\ChatGroupSync;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

class ManageChatGroups extends ManageRecords
{
    protected static string $resource = ChatGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')->label('Automatische Gruppen abgleichen')->icon('heroicon-o-arrow-path')->color('gray')
                ->action(function () {
                    app(ChatGroupSync::class)->run();
                    Notification::make()->title('Gruppen abgeglichen')->success()->send();
                }),
            Action::make('create')->label('Gruppe anlegen')->icon('heroicon-o-plus')
                ->schema(fn (Schema $schema) => ChatGroupResource::form($schema))
                ->action(fn (array $data) => ChatGroupResource::saveGroup(new ChatGroup(['type' => 'custom']), $data)),
        ];
    }
}
