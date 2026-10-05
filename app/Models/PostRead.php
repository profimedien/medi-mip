<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRead extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['read_at' => 'datetime', 'confirmed_at' => 'datetime'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
