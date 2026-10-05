<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Redaktion: deutsche Datumsformate statt Browser-Format
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn ($c) => $c->native(false)->displayFormat('d.m.Y H:i')->firstDayOfWeek(1)->seconds(false));
        // Nein-Werte dezent grau statt rotem X
        \Filament\Tables\Columns\IconColumn::configureUsing(fn ($c) => $c->falseColor('gray')->falseIcon('heroicon-o-minus-small'));
        \Filament\Forms\Components\DatePicker::configureUsing(fn ($c) => $c->native(false)->displayFormat('d.m.Y')->firstDayOfWeek(1));
        //
    }
}
