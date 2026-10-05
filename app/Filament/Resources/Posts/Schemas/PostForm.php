<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make([
                Section::make([
                    TextInput::make('title')->label('Titel')->required()->maxLength(255),
                    Textarea::make('teaser')->label('Kurztext')->rows(2)->helperText('Erscheint in der Übersicht und in Benachrichtigungen.'),
                    RichEditor::make('body')->label('Inhalt')
                        ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
                ]),
                Section::make('Bild und Anhänge')->schema([
                    Fields::upload('image', 'Titelbild', images: true),
                    Fields::upload('attachments', 'Anhänge (Dokumente, Bilder)', multiple: true),
                ])->collapsible(),
            ])->columnSpan(2),
            Group::make([
                Section::make('Veröffentlichung')->schema([
                    DateTimePicker::make('published_at')->label('Veröffentlichen am')->default(now())->seconds(false)
                        ->helperText('Leer lassen für Entwurf. Ein Zeitpunkt in der Zukunft plant den Beitrag ein.'),
                    Toggle::make('pinned')->label('Oben anheften'),
                    Toggle::make('notify_mail')->label('Zusätzlich per E-Mail informieren')
                        ->helperText('Push und Hinweis im Portal gibt es immer.'),
                    Toggle::make('requires_confirmation')->label('Lesebestätigung anfordern'),
                ]),
                Fields::audience(),
            ])->columnSpan(1),
        ]);
    }
}
