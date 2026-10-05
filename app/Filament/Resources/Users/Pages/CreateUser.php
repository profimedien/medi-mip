<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\ChatGroupSync;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + [
            'status' => 'invited',
            'start_code' => User::generateStartCode(),
            'must_change_password' => true,
        ];
    }

    protected function afterCreate(): void
    {
        if ($this->record->roles()->doesntExist()) {
            $this->record->assignRole('mitarbeiter');
        }
        app(ChatGroupSync::class)->run();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Angelegt. Den Zugangszettel drucken Sie über die Zeile der Person.';
    }
}
