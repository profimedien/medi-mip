<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contact extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** intern: Kollegin oder Kollege aus den Mitarbeitenden, extern: frei gepflegt (z. B. EDV-Dienstleister, Betriebsarzt) */
    public const TYPES = ['internal' => 'Intern', 'external' => 'Extern'];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // externe Ansprechpartner hängen an keiner Person aus dem Portal
        static::saving(fn (Contact $c) => $c->type === 'external' ? $c->user_id = null : null);
    }

    /** Einrichtungen, für die der Ansprechpartner gilt. Keine Zuordnung = alle Einrichtungen */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->orderBy('locations.sort');
    }

    /** Gilt für diese Einrichtung: dort zugeordnet oder ohne Zuordnung (alle) */
    public function scopeForLocation(Builder $query, ?int $locationId): Builder
    {
        return $query->where(fn ($q) => $q->whereDoesntHave('locations')
            ->when($locationId, fn ($q) => $q->orWhereHas('locations', fn ($l) => $l->where('locations.id', $locationId))));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
    }
}
