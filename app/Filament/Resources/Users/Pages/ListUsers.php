<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')->label('Import aus Zeiterfassung')->icon('heroicon-o-arrow-up-tray')->color('gray')
                ->visible(fn () => auth()->user()->isGesamtAdmin())
                ->modalDescription('Excel- oder CSV-Datei mit Kopfzeile: '.implode(', ', UserImport::COLUMNS).'. Bekannte Personen (gleiche Personalnummer) werden aktualisiert, neue erhalten einen Startcode.')
                ->schema([
                    FileUpload::make('file')->label('Datei')->disk('local')->directory('imports')->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),
                    Toggle::make('dry')->label('Nur prüfen, nichts speichern')->default(true),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $report = (new UserImport)->run($path, (bool) $data['dry']);
                    Storage::disk('local')->delete($data['file']);
                    $title = ($data['dry'] ? 'Prüfung: ' : 'Import: ').$report['created'].' neu, '.$report['updated'].' aktualisiert';
                    Notification::make()->title($title)
                        ->body($report['errors'] ? implode("\n", array_slice($report['errors'], 0, 15)) : 'Keine Fehler gefunden.')
                        ->{$report['errors'] ? 'warning' : 'success'}()->persistent()->send();
                }),
            CreateAction::make()->label('Person anlegen'),
        ];
    }
}
