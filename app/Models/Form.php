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

    /** Standard für neue Rückmeldungen, je Rückmeldung frei änderbar */
    public const DEFAULT_CHOICES = ['Zusage', 'Absage'];

    /** Vorschläge im Eingabefeld der Redaktion */
    public const SUGGESTED_CHOICES = ['Zusage', 'Absage', 'Vielleicht', 'Zur Kenntnis genommen', 'Nehme online teil'];

    /** Bei diesen Antworten entfallen die Zusatzfragen */
    public const DECLINE_WORDS = ['absage', 'nein', 'kann nicht', 'nicht dabei'];

    public static function isDecline(?string $choice): bool
    {
        $c = mb_strtolower(trim((string) $choice));

        return $c !== '' && collect(self::DECLINE_WORDS)->contains(fn ($w) => str_contains($c, $w));
    }

    protected $guarded = ['id'];

    protected $casts = [
        'choices' => 'array',
        'requires_signature' => 'boolean',
        'send_reminder' => 'boolean',
        'reminder_hours' => 'integer',
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
        return array_values($this->choices ?? []);
    }
}
