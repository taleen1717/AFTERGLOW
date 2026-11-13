<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeComment extends Model
{
    protected $fillable = ['user_id', 'code_snippet_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function snippet(): BelongsTo
    {
        return $this->belongsTo(CodeSnippet::class, 'code_snippet_id');
    }
}
