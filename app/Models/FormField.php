<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    public const TYPES = [
        'boolean' => 'Ja/Nein',
        'select' => 'Auswahl (eine)',
        'multiselect' => 'Auswahl (mehrere)',
        'text' => 'Freitext',
        'number' => 'Zahl',
    ];

    /** Standardfrage bei neuen Rückmeldungen (Veranstaltungen), je Rückmeldung entfernbar */
    public const MEDIA_CONSENT = 'Ich bin einverstanden, dass bei der Veranstaltung Bild- und Tonaufnahmen von mir gemacht und für die interne Kommunikation von medi genutzt werden.';

    protected $guarded = ['id'];

    protected $casts = ['options' => 'array', 'required' => 'boolean'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
