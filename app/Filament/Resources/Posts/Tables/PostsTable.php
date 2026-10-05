<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->wrap()->weight('medium'),
                TextColumn::make('audience')->label('Zielgruppe')->state(fn (Post $r) => $r->audienceLabel())->badge()->color('info'),
                TextColumn::make('published_at')->label('Veröffentlicht')->dateTime('d.m.Y H:i')->sortable()->placeholder('Entwurf'),
                IconColumn::make('pinned')->label('Angeheftet')->icon(fn ($state) => $state ? 'heroicon-s-bookmark' : 'heroicon-o-minus-small')->color(fn ($state) => $state ? 'primary' : 'gray')->toggleable(),
                TextColumn::make('reads_count')->counts('reads')->label('Gelesen')->sortable(),
                TextColumn::make('confirmations')->label('Bestätigt')
                    ->state(fn (Post $r) => $r->requires_confirmation ? $r->reads()->whereNotNull('confirmed_at')->count().' / '.$r->audienceMembers()->count() : '–'),
            ])
            ->recordActions([
                Action::make('report')->label('Auswertung')->icon('heroicon-o-chart-bar')->iconButton()->tooltip('Wer hat gelesen und bestätigt?')
                    ->modalHeading(fn (Post $r) => 'Auswertung: '.$r->title)
                    ->modalContent(fn (Post $r) => view('filament.read-report', self::report($r)))
                    ->modalSubmitAction(false)->modalCancelActionLabel('Schließen'),
                EditAction::make()->iconButton()->tooltip('Bearbeiten'),
                DeleteAction::make()->iconButton()->tooltip('Löschen'),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function report(Post $post): array
    {
        $members = $post->audienceMembers()->with('location')->orderBy('last_name')->get();
        $reads = $post->reads()->get()->keyBy('user_id');

        return [
            'requiresConfirmation' => $post->requires_confirmation,
            'total' => $members->count(),
            'read' => $members->filter(fn ($u) => $reads->has($u->id))->count(),
            'confirmed' => $members->filter(fn ($u) => $reads->get($u->id)?->confirmed_at)->count(),
            'rows' => $members->map(fn ($u) => [
                'name' => $u->name,
                'location' => $u->location?->short_name,
                'read_at' => $reads->get($u->id)?->read_at,
                'confirmed_at' => $reads->get($u->id)?->confirmed_at,
            ]),
        ];
    }
}
