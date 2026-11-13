<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisualBoard extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'mood', 'image_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\BoardLike::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\BoardComment::class)->latest();
    }
}
