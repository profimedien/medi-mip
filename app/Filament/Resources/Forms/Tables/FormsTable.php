<?php

namespace App\Filament\Resources\Forms\Tables;

use App\Models\Form;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->wrap()->weight('medium'),
                TextColumn::make('audience')->label('Zielgruppe')->state(fn (Form $r) => $r->audienceLabel())->badge()->color('info'),
                TextColumn::make('deadline')->label('Frist')->dateTime('d.m.Y H:i')->sortable()->placeholder('ohne'),
                TextColumn::make('progress')->label('Antworten')
                    ->state(fn (Form $r) => $r->responses()->count().' / '.$r->audienceMembers()->count()),
                TextColumn::make('published_at')->label('Veröffentlicht')->dateTime('d.m.Y')->placeholder('Entwurf')->toggleable(),
            ])
            ->recordActions([EditAction::make()->iconButton()->tooltip('Bearbeiten und Antworten ansehen'), DeleteAction::make()->iconButton()->tooltip('Löschen')])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
