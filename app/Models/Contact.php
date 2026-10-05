<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contact extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
