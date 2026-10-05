<?php

namespace App\Filament\Widgets;

use App\Models\ChatMessage;
use App\Models\FaqQuestion;
use App\Models\MaterialRequest;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\AdminScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortalStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $admin = auth()->user();
        $users = AdminScope::users($admin);
        $stats = [
            Stat::make('Aktive Konten', (clone $users)->where('status', 'active')->count())
                ->description((clone $users)->where('status', 'invited')->count().' noch nicht angemeldet')->color('success'),
            Stat::make('Angemeldet in den letzten 7 Tagen', (clone $users)->where('last_login_at', '>=', now()->subDays(7))->count()),
            Stat::make('Chat-Nachrichten heute', ChatMessage::whereDate('created_at', today())->count()),
        ];
        if ($admin->isGesamtAdmin()) {
            $stats[] = Stat::make('Offene Eingänge', Suggestion::where('status', 'new')->count() + MaterialRequest::where('status', 'new')->count() + FaqQuestion::where('status', 'open')->count())
                ->description('Vorschläge, Materialanfragen, FAQ-Fragen')->color('danger');
        }

        return $stats;
    }
}
