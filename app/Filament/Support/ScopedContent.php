<?php

namespace App\Filament\Support;

use App\Support\AdminScope;
use Illuminate\Database\Eloquent\Builder;

/** Inhalte mit Zielgruppe: Admins sehen nur, was in ihrem Bereich liegt oder von ihnen stammt */
trait ScopedContent
{
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::content(parent::getEloquentQuery(), auth()->user());
    }
}
