<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    public const KEYS = ['suggestion_topics', 'suggestion_recipients', 'material_recipients', 'referral_recipients', 'faq_recipients', 'referral_text'];

    public static function canAccess(): bool
    {
        return auth()->user()?->isGesamtAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all());
    }

    public function form(Schema $schema): Schema
    {
        $hint = 'Eine oder mehrere E-Mail-Adressen, mit Komma getrennt.';

        return $schema->statePath('data')->components([
            Section::make('Verbesserungsvorschläge')->schema([
                Textarea::make('suggestion_topics')->label('Themen (eins pro Zeile)')->rows(5),
                TextInput::make('suggestion_recipients')->label('Empfänger')->helperText($hint),
            ])->columns(2),
            Section::make('Weitere Empfänger')->schema([
                TextInput::make('material_recipients')->label('Materialanfragen')->helperText($hint),
                TextInput::make('faq_recipients')->label('Eingereichte FAQ-Fragen')->helperText($hint),
                TextInput::make('referral_recipients')->label('Mitarbeiter werben Mitarbeiter')->helperText($hint),
            ])->columns(3),
            Section::make('Mitarbeiter werben Mitarbeiter')->schema([
                Textarea::make('referral_text')->label('Text zu Prämie und Bedingungen')->rows(5),
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
