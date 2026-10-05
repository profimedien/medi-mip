<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/** Begrüßung mit Schnellaktionen, im Stil des Portals */
class RedaktionHero extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.redaktion-hero';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $hour = (int) now()->format('G');

        $actions = [
            ['label' => 'News schreiben', 'url' => route('filament.admin.resources.posts.create'), 'icon' => 'newspaper'],
            ['label' => 'Rückmeldung anlegen', 'url' => route('filament.admin.resources.forms.create'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Termine', 'url' => route('filament.admin.resources.events.index'), 'icon' => 'calendar-days'],
            ['label' => 'Person anlegen', 'url' => route('filament.admin.resources.users.create'), 'icon' => 'user-plus'],
            ['label' => 'Zum Portal', 'url' => url('/'), 'icon' => 'arrow-top-right-on-square', 'blank' => true],
        ];

        return [
            'greeting' => $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend'),
            'name' => $user->first_name,
            'scope' => $user->isGesamtAdmin() ? 'Gesamt-Admin · alle Standorte' : ($user->adminScopes->map(fn ($s) => $s->location?->short_name ?? $s->department?->name)->filter()->join(', ') ?: 'Admin'),
            'actions' => $actions,
        ];
    }
}
