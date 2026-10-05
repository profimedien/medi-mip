<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public const STATUS = ['invited' => 'Eingeladen', 'active' => 'Aktiv', 'disabled' => 'Gesperrt'];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('last_name')->label('Name')->formatStateUsing(fn (User $r) => $r->last_name.', '.$r->first_name)
                    ->searchable(['first_name', 'last_name', 'username', 'personnel_number'])->sortable()->weight('medium'),
                TextColumn::make('username')->label('Benutzername')->toggleable(),
                TextColumn::make('location.short_name')->label('Standort')->sortable(),
                TextColumn::make('departments.name')->label('Abteilungen')->badge()->limitList(2),
                TextColumn::make('roles.name')->label('Rolle')->badge()->color('warning')
                    ->formatStateUsing(fn ($state) => $state === 'mitarbeiter' ? null : $state)->toggleable(),
                IconColumn::make('email')->label('E-Mail')->boolean(fn ($state) => filled($state))->state(fn (User $r) => filled($r->email)),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'active' => 'success', 'invited' => 'warning', default => 'danger' }),
                TextColumn::make('last_login_at')->label('Letzte Anmeldung')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('location_id')->label('Standort')->options(fn () => Location::orderBy('sort')->pluck('short_name', 'id')),
                SelectFilter::make('departments')->label('Abteilung')->relationship('departments', 'name')->preload(),
                SelectFilter::make('status')->label('Status')->options(self::STATUS),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sheet')->label('Zugangszettel')->icon('heroicon-o-printer')->color('gray')
                    ->visible(fn (User $r) => filled($r->start_code))
                    ->url(fn (User $r) => route('access-sheet', ['ids' => $r->id]), shouldOpenInNewTab: true),
                Action::make('reset')->label('Neuer Startcode')->icon('heroicon-o-key')->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Das bisherige Passwort wird ungültig. Die Person meldet sich mit dem neuen Startcode an und vergibt ein neues Passwort.')
                    ->visible(fn (User $r) => $r->status !== 'disabled')
                    ->action(function (User $r) {
                        $code = $r->resetAccess();
                        Notification::make()->title('Neuer Startcode für '.$r->name.': '.$code)->body('Den Zugangszettel finden Sie in der Zeile der Person.')->success()->persistent()->send();
                    }),
                Action::make('toggle')->label(fn (User $r) => $r->status === 'disabled' ? 'Entsperren' : 'Sperren')
                    ->icon(fn (User $r) => $r->status === 'disabled' ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (User $r) => $r->status === 'disabled' ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->hidden(fn (User $r) => $r->id === auth()->id())
                    ->action(fn (User $r) => $r->update(['status' => $r->status === 'disabled' ? ($r->password ? 'active' : 'invited') : 'disabled'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('sheets')->label('Zugangszettel drucken')->icon('heroicon-o-printer')
                        ->action(function (Collection $records, $livewire) {
                            $ids = $records->filter(fn ($u) => filled($u->start_code))->pluck('id');
                            if ($ids->isEmpty()) {
                                Notification::make()->title('Keine der ausgewählten Personen hat einen offenen Startcode.')->warning()->send();

                                return;
                            }
                            $livewire->js('window.open('.json_encode(route('access-sheet', ['ids' => $ids->join(',')])).', "_blank")');
                        })->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
