<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use HasAudience, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'pinned' => 'boolean',
        'notify_mail' => 'boolean',
        'requires_confirmation' => 'boolean',
        'published_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PostRead::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
        $this->addMediaCollection('image')->singleFile();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function readBy(User $user): ?PostRead
    {
        return $this->reads->firstWhere('user_id', $user->id);
    }
}
