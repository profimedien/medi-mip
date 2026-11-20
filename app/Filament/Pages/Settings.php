<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Einstellungen';

    protected static ?string $navigationLabel = 'Empfänger und Texte';

    protected static ?string $title = 'Empfänger und Texte';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public const KEYS = ['classified_topics', 'suggestion_topics', 'suggestion_recipients', 'referral_recipients', 'faq_recipients', 'referral_text', 'help_text', 'chat_uploads', 'chat_retention_days', 'chat_rules'];

    public static function canAccess(): bool
    {
        return auth()->user()?->isGesamtAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all()
            + ['classified_topics' => Setting::get('classified_topics', \App\Models\Classified::DEFAULT_TOPICS)]);
        $this->data['chat_uploads'] = (bool) Setting::get('chat_uploads', false);
        $this->data['chat_retention_days'] = (int) Setting::get('chat_retention_days', 365);
    }

    public function form(Schema $schema): Schema
    {
        $hint = 'Eine oder mehrere E-Mail-Adressen, mit Komma getrennt.';

        return $schema->statePath('data')->components([
            Section::make('Ideenbörse')->schema([
                Textarea::make('suggestion_topics')->label('Themen (eins pro Zeile)')->rows(5),
                TextInput::make('suggestion_recipients')->label('Empfänger')->helperText($hint),
            ])->columns(2),
            Section::make('Weitere Empfänger')->schema([
                TextInput::make('faq_recipients')->label('Eingereichte FAQ-Fragen')->helperText($hint),
                TextInput::make('referral_recipients')->label('Mitarbeiter werben Mitarbeiter')->helperText($hint),
            ])->columns(2),
            Section::make('Chat')->schema([
                Toggle::make('chat_uploads')->label('Bilder und Dateien im Chat erlauben')
                    ->helperText('Aus: Im Chat kann nur Text geschrieben werden. Bereits geteilte Dateien bleiben sichtbar.'),
                TextInput::make('chat_retention_days')->label('Nachrichten automatisch löschen nach (Tage)')
                    ->numeric()->minValue(0)->maxValue(3650)->required()
                    ->helperText('Gilt für alle Gruppen und Direktchats. 0 = dauerhaft speichern. Je Gruppe abweichend unter Inhalte > Chatgruppen. Gelöschte Nachrichten und Dateien sind endgültig weg.'),
                Textarea::make('chat_rules')->label('Chatregeln')->rows(8)->columnSpanFull()
                    ->helperText('Erscheint im Chat über das Waage-Symbol und beim ersten Öffnen einmal als Hinweis. Links (https://…) werden anklickbar.'),
            ])->columns(2),
            Section::make('Marktplatz')->schema([
                Textarea::make('classified_topics')->label('Kategorien (eine pro Zeile)')->rows(7)
                    ->helperText('Bestehende Anzeigen behalten ihre Kategorie, auch wenn sie hier entfernt wird.'),
            ]),
            Section::make('Mitarbeiter werben Mitarbeiter')->schema([
                Textarea::make('referral_text')->label('Text zu Prämie und Bedingungen')->rows(5),
            ]),
            Section::make('medi hilft')->schema([
                Textarea::make('help_text')->label('Informationen und Anträge')->rows(6)
                    ->helperText('Erscheint unter Mitmachen im Reiter „medi hilft“. Links (https://…) werden anklickbar, z. B. auf einen Antrag als PDF.'),
            ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::set($key, $value);
        }
        Notification::make()->title('Gespeichert')->success()->send();
    }
}
