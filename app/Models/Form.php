<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Form extends Model implements HasMedia
{
    use HasAudience, InteractsWithMedia;

    public const CHOICES = [
        'zusage' => 'Zusage',
        'absage' => 'Absage',
        'kenntnis' => 'Zur Kenntnis genommen',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'choices' => 'array',
        'requires_signature' => 'boolean',
        'send_reminder' => 'boolean',
        'deadline' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'published_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('attachments');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isOpen(): bool
    {
        return ! $this->deadline || $this->deadline->isFuture();
    }

    public function choiceOptions(): array
    {
        return array_intersect_key(self::CHOICES, array_flip($this->choices ?? []));
    }
}
