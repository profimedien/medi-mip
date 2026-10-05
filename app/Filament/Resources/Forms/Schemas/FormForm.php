<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Filament\Support\Fields;
use App\Models\Form;
use App\Models\FormField;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make([
                Section::make([
                    TextInput::make('title')->label('Titel')->required()->maxLength(255),
                    RichEditor::make('description')->label('Beschreibung')
                        ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
                    TagsInput::make('choices')->label('Antwortmöglichkeiten')
                        ->default(Form::DEFAULT_CHOICES)->suggestions(Form::SUGGESTED_CHOICES)->reorderable()
                        ->placeholder('Antwort eingeben und Enter drücken')
                        ->helperText('Standard: Zusage und Absage. Frei änderbar, z. B. „Vielleicht“. Bei „Absage“ oder „Nein“ entfallen die Zusatzfragen. Leer lassen, wenn nur die Zusatzfragen beantwortet werden.'),
                    Toggle::make('requires_signature')->label('Unterschrift verlangen'),
                ]),
                Section::make('Zusatzfragen')
                    ->description('Werden bei Zusage bzw. immer gestellt, wenn es keine Zu-/Absage gibt. Beispiel: Essenswunsch, Begleitpersonen, Unverträglichkeiten.')
                    ->schema([
                        Repeater::make('fields')->relationship()->hiddenLabel()
                            ->orderColumn('sort')->collapsible()->cloneable()
                            ->itemLabel(fn (array $state) => $state['label'] ?? null)
                            ->addActionLabel('Frage hinzufügen')
                            ->columns(2)
                            ->schema([
                                TextInput::make('label')->label('Frage')->required()->columnSpan(2),
                                Select::make('type')->label('Art')->options(FormField::TYPES)->required()->live()->default('text'),
                                Toggle::make('required')->label('Pflichtfeld')->inline(false),
                                TagsInput::make('options')->label('Auswahlmöglichkeiten')->columnSpan(2)
                                    ->placeholder('Option eingeben und Enter drücken')
                                    ->visible(fn (Get $get) => in_array($get('type'), ['select', 'multiselect'])),
                            ]),
                    ])->collapsible(),
                Section::make('Bild und Anhänge')->schema([
                    Fields::upload('image', 'Bild oder Logo', images: true),
                    Fields::upload('attachments', 'PDF-Anhänge', multiple: true),
                ])->collapsed(),
            ])->columnSpan(2),
            Group::make([
                Section::make('Zeitraum')->schema([
                    DateTimePicker::make('published_at')->label('Veröffentlichen am')->default(now())->seconds(false),
                    DateTimePicker::make('deadline')->label('Frist')->seconds(false),
                    Toggle::make('send_reminder')->label('Erinnerung vor Fristende')->default(true)->live()
                        ->helperText('Geht an alle, die noch nicht geantwortet haben.'),
                    Select::make('reminder_hours')->label('Erinnern')->default(48)->required()
                        ->options([4 => '4 Stunden vorher', 12 => '12 Stunden vorher', 24 => '1 Tag vorher', 48 => '2 Tage vorher (Standard)', 72 => '3 Tage vorher', 168 => '1 Woche vorher'])
                        ->visible(fn (Get $get) => (bool) $get('send_reminder')),
                ]),
                Fields::audience(),
            ])->columnSpan(1),
        ]);
    }
}
