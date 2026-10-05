<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('medi Redaktion')
            ->brandLogo(asset('images/medi-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/icon-192.png'))
            ->colors([
                // V3-Palette: die Markenfarbe liegt auf Stufe 500, Buttons bekommen Stufe 600 mit weißer Schrift
                'primary' => Color::generateV3Palette('#0032ba'),
                'danger' => Color::generateV3Palette('#e0003a'),
                'info' => Color::generateV3Palette('#1e88d2'),
            ])
            ->font('Ubuntu')
            ->navigationGroups([
                NavigationGroup::make('Inhalte'),
                NavigationGroup::make('Rückmeldung und Beteiligung'),
                NavigationGroup::make('Wissen'),
                NavigationGroup::make('Personal und Struktur'),
                NavigationGroup::make('Einstellungen'),
            ])
            ->navigationItems([
                NavigationItem::make('Zum Portal')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->sort(100),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
