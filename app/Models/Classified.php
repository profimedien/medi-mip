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
