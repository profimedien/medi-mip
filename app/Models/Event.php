<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasAudience;

    /** Kategorien im Kalender (Feedback 11.11.2026: Fortbildung ergänzt) */
    public const CATEGORIES = ['event' => 'Termin', 'training' => 'Fortbildung'];

    protected $guarded = ['id'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
