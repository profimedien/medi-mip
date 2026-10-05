<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->orderBy('sort')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function getAddressAttribute(): string
    {
        return trim($this->street.', '.$this->zip.' '.$this->city, ' ,');
    }
}
