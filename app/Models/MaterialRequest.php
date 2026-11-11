<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materialanfrage: seit 11.11.2026 aus Portal, Redaktion und Statistik entfernt (Wunsch medi).
 * Tabelle und vorhandene Einträge bleiben bewusst erhalten, das Model nur für den Datenbestand.
 */
class MaterialRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['needed_by' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
