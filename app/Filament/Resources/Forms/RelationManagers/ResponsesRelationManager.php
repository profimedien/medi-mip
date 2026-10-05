<?php

namespace App\Filament\Resources\Forms\RelationManagers;

use App\Exports\FormResponsesExport;
use App\Models\Form;
use App\Models\FormResponse;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Antworten';

    public function table(Table $table): Table
    {
        /** @var Form $form */
        $form = $this->getOwnerRecord();

        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns(array_merge([
                TextColumn::make('user.first_name')->label('Name')
                    ->formatStateUsing(fn (FormResponse $r) => $r->user?->name)->searchable(['first_name', 'last_name']),
                TextColumn::make('user.location.short_name')->label('Standort'),
                TextColumn::make('choice')->label('Antwort')->badge()
                    
                    ->color(fn (?string $state) => Form::isDecline($state) ? 'danger' : (mb_strtolower((string) $state) === 'zusage' ? 'success' : 'gray')),
            ], $form->fields->map(fn ($field) => TextColumn::make('answer_'.$field->id)->label($field->label)
                ->state(function (FormResponse $r) use ($field) {
                    $a = $r->answers[$field->id] ?? null;

                    return is_array($a) ? implode(', ', $a) : ($field->type === 'boolean' ? ($a ? 'Ja' : ($a === null ? '' : 'Nein')) : $a);
                })->wrap())->all(), [
                IconColumn::make('signature_path')->label('Unterschrift')->boolean(fn ($state) => filled($state))
                    ->state(fn (FormResponse $r) => filled($r->signature_path)),
                TextColumn::make('updated_at')->label('Am')->dateTime('d.m. H:i')->sortable(),
            ]))
            ->filters([SelectFilter::make('choice')->label('Antwort')->options(fn () => array_combine($form->choices ?? [], $form->choices ?? []))])
            ->headerActions([
                Action::make('export')->label('Als Excel herunterladen')->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => (new FormResponsesExport($form))->download()),
                Action::make('missing')->label('Wer fehlt noch?')->icon('heroicon-o-user-minus')->color('gray')
                    ->modalHeading('Noch keine Antwort')
                    ->modalContent(fn () => view('filament.missing-list', [
                        'people' => $form->audienceMembers()->whereDoesntHave('formResponses', fn ($q) => $q->where('form_id', $form->id))
                            ->with('location')->orderBy('last_name')->get(),
                    ]))
                    ->modalSubmitAction(false)->modalCancelActionLabel('Schließen'),
            ])
            ->recordActions([
                Action::make('signature')->label('Unterschrift')->icon('heroicon-o-pencil')
                    ->visible(fn (FormResponse $r) => filled($r->signature_path))
                    ->modalContent(fn (FormResponse $r) => view('filament.signature', ['src' => 'data:image/png;base64,'.base64_encode(\Storage::disk('local')->get($r->signature_path))]))
                    ->modalSubmitAction(false)->modalCancelActionLabel('Schließen'),
                DeleteAction::make(),
            ]);
    }
}
