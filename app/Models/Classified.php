<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Classified extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const CATEGORIES = ['private' => 'Privat', 'business' => 'Dienstlich'];

    public const RUNTIME_DAYS = 30;

    public const DEFAULT_TOPICS = "Verkaufen\nVerschenken\nSuche\nMitfahrgelegenheit\nWohnen\nTherapiematerial\nSonstiges";

    /** Kategorien, von der Redaktion unter "Empfänger und Texte" pflegbar */
    public static function topics(): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) Setting::get('classified_topics', self::DEFAULT_TOPICS)))));
    }

    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'extended' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(480)->height(480)->nonQueued();
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
