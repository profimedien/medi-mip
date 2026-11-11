<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /**
     * Kalenderfarben je Standort. Bewusst ohne Grün und Orange: die stehen für Jubiläen und Geburtstage.
     * Schlüssel ist der Farbwert, damit er direkt als style genutzt werden kann.
     */
    public const COLORS = [
        '#2563eb' => 'Blau',
        '#7c3aed' => 'Violett',
        '#db2777' => 'Pink',
        '#0891b2' => 'Türkis',
        '#dc2626' => 'Rot',
        '#1e3a8a' => 'Dunkelblau',
        '#92400e' => 'Braun',
        '#c026d3' => 'Magenta',
        '#475569' => 'Schiefergrau',
        '#ca8a04' => 'Senfgelb',
    ];

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->orderBy('sort')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function getAddressAttribute(): string
    {
        return trim($this->street.', '.$this->zip.' '.$this->city, ' ,');
    }
}
