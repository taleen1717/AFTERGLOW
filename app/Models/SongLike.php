<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SongLike extends Model
{
    protected $fillable = ['user_id', 'favorite_song_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(FavoriteSong::class, 'favorite_song_id');
    }
}
