<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['in_menu' => 'boolean'];

    public const CATEGORIES = [
        'schnellzugriff' => 'Schnellzugriff',
        'gesetze' => 'Aushangpflichtige Gesetze',
    ];

    // Aushangpflichtige Gesetze laut medi (Mail vom 20.11.2026), offizielle Fassung auf gesetze-im-internet.de
    public const LAWS = [
        ['Arbeitszeitgesetz', 'https://www.gesetze-im-internet.de/arbzg/', 'ArbZG'],
        ['Allgemeines Gleichbehandlungsgesetz', 'https://www.gesetze-im-internet.de/agg/', 'AGG'],
        ['Mutterschutzgesetz', 'https://www.gesetze-im-internet.de/muschg_2018/', 'MuSchG'],
        ['Jugendarbeitsschutzgesetz', 'https://www.gesetze-im-internet.de/jarbschg/', 'JArbSchG'],
        ['Arbeitsstättenverordnung', 'https://www.gesetze-im-internet.de/arbst_ttv_2004/', 'ArbStättV'],
    ];

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
