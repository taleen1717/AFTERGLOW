<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteSong extends Model
{
    public const MOODS = ['Night Drive', 'Icy Dream', 'Neon Rush', 'Art Mode', 'Soft Hours', 'Chaos', 'Love', 'Retro'];
    protected $fillable = ['title', 'artist', 'url', 'artwork_url', 'platform', 'mood', 'quote_id', 'is_on_repeat'];

    protected function casts(): array { return ['is_on_repeat' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }

    public function getResolvedPlatformAttribute(): string
    {
        return ($this->platform === 'spotify' || str_contains($this->url ?? '', 'open.spotify.com/')) ? 'spotify' : 'youtube';
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $parts = parse_url($this->url ?: '');
        if (!is_array($parts)) return null;
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if ($host === 'open.spotify.com' && preg_match('~^/(?:intl-[a-z]{2,5}/)?(track|album|playlist|artist|episode|show)/([A-Za-z0-9]{8,64})/?$~i', $path, $m)) {
            return 'https://open.spotify.com/embed/' . strtolower($m[1]) . '/' . $m[2];
        }
        $id = null;
        if ($host === 'youtu.be') $id = trim($path, '/');
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $id = $query['v'] ?? null;
            if (!$id && preg_match('~^/(?:shorts|embed)/([A-Za-z0-9_-]{11})~', $path, $m)) $id = $m[1];
        }
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)
            ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
    }
}
