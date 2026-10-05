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

    protected $guarded = ['id'];

    protected $casts = ['options' => 'array', 'required' => 'boolean'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
