<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, InteractsWithMedia, Notifiable;

    public const ADMIN_ROLES = ['gesamt-admin', 'standort-admin', 'abteilungs-admin'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'start_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'entry_date' => 'date',
            'password' => 'hashed',
            'start_code' => 'encrypted',
            'must_change_password' => 'boolean',
            'profile_visible' => 'boolean',
            'show_birthday' => 'boolean',
            'show_anniversary' => 'boolean',
            'notification_settings' => 'array',
        ];
    }

    // --- Beziehungen -------------------------------------------------------

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function adminScopes(): HasMany
    {
        return $this->hasMany(AdminScope::class);
    }

    public function postReads(): HasMany
    {
        return $this->hasMany(PostRead::class);
    }

    public function chatMemberships(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    // --- Namen und Darstellung --------------------------------------------

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function initials(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    public function avatarUrl(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        return $media ? route('media.show', $media) : null;
    }

    // --- Rollen und Zuständigkeiten ---------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active' && $this->hasAnyRole(self::ADMIN_ROLES);
    }

    public function isGesamtAdmin(): bool
    {
        return $this->hasRole('gesamt-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(self::ADMIN_ROLES);
    }

    /** Standorte, für die diese Person Admin ist (Gesamt-Admin: alle) */
    public function adminLocationIds(): array
    {
        if ($this->isGesamtAdmin()) {
            return Location::pluck('id')->all();
        }

        return $this->adminScopes->pluck('location_id')->filter()->unique()->values()->all();
    }

    /** Abteilungen, für die diese Person Admin ist (Gesamt-Admin: alle) */
    public function adminDepartmentIds(): array
    {
        if ($this->isGesamtAdmin()) {
            return Department::pluck('id')->all();
        }

        return $this->adminScopes->pluck('department_id')->filter()->unique()->values()->all();
    }

    public function departmentIds(): array
    {
        return once(fn () => $this->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->all());
    }

    // --- Zugang ------------------------------------------------------------

    public static function generateStartCode(): string
    {
        // gut lesbar: keine 0/O, 1/I/l
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    /** Neuen Startcode setzen: Passwort wird ungültig, Person muss beim Login ein neues vergeben */
    public function resetAccess(): string
    {
        $code = self::generateStartCode();
        $this->forceFill([
            'start_code' => $code,
            'password' => null,
            'must_change_password' => true,
            'status' => $this->status === 'disabled' ? 'disabled' : 'invited',
        ])->save();

        return $code;
    }

    public static function makeUsername(string $first, string $last): string
    {
        $base = Str::of($first.'.'.$last)->lower()->ascii()->replaceMatches('/[^a-z0-9.]/', '')->toString();
        $name = $base;
        $i = 2;
        while (static::where('username', $name)->exists()) {
            $name = $base.$i++;
        }

        return $name;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function notificationSetting(string $key, mixed $default = null): mixed
    {
        $defaults = ['mail_news' => true, 'mail_forms' => true, 'mail_chat_digest' => true, 'push' => true];

        return ($this->notification_settings ?? [])[$key] ?? $defaults[$key] ?? $default;
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }
}
