<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardLike extends Model
{
    protected $fillable = ['user_id', 'visual_board_id'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function board(): BelongsTo { return $this->belongsTo(VisualBoard::class, 'visual_board_id'); }
}
