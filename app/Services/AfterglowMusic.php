<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AfterglowMusic
{
    public function youtubeSearch(string $query): array
    {
        $key = config('afterglow_music.youtube_key');
        if (!$key) {
            throw ValidationException::withMessages(['search' => 'YouTube is not connected yet. Add YOUTUBE_API_KEY in .env.']);
        }
        return Cache::remember('ag:yt:' . hash('sha256', mb_strtolower($query)), now()->addMinutes(30), function () use ($query, $key) {
            $r = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'snippet', 'type' => 'video', 'videoEmbeddable' => 'true',
                'videoSyndicated' => 'true', 'regionCode' => 'SA',
                'maxResults' => 8, 'q' => $query . ' music', 'key' => $key,
            ]);
            if (!$r->successful()) {
                throw ValidationException::withMessages(['search' => 'YouTube search is unavailable. Check your API key and quota.']);
            }
            $items = collect($r->json('items', []))->filter(function ($item) {
                $id = data_get($item, 'id.videoId');
                return is_string($id) && (bool) preg_match('/^[A-Za-z0-9_-]{11}$/', $id);
            });
            $ids = $items->pluck('id.videoId')->all();
            if (!$ids) return [];

            // Check the live embed flag and known region restrictions in one inexpensive batch.
            // This REDUCES blocked results, but only the player can tell us for certain.
            $details = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'status,contentDetails', 'id' => implode(',', $ids), 'key' => $key,
            ]);
            if (!$details->successful()) {
                throw ValidationException::withMessages(['search' => 'Could not check playback availability. Please retry.']);
            }
            $playable = collect($details->json('items', []))->filter(fn ($video) =>
                data_get($video, 'status.embeddable') === true &&
                data_get($video, 'status.privacyStatus') === 'public' &&
                $this->allowedInSaudiArabia($video)
            )->pluck('id')->all();
            return $items->filter(fn ($item) => in_array(data_get($item, 'id.videoId'), $playable, true))
                ->map(function ($item) {
                    $id = data_get($item, 'id.videoId');
                    return [
                        'video_id' => $id,
                        'title' => (string) data_get($item, 'snippet.title', ''),
                        'artist' => (string) data_get($item, 'snippet.channelTitle', ''),
                        'artwork_url' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
                    ];
                })->values()->all();
        });
    }

    public function youtubeVideo(string $id): array
    {
        $key = config('afterglow_music.youtube_key');
        if (!$key || !preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            throw ValidationException::withMessages(['video_id' => 'Invalid YouTube video selection.']);
        }
        $r = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,status,contentDetails', 'id' => $id, 'key' => $key,
        ]);
        if (!$r->successful()) {
            throw ValidationException::withMessages(['video_id' => 'Could not verify the selected YouTube video.']);
        }
        $video = $r->json('items.0');
        if (!$video || data_get($video, 'status.embeddable') !== true ||
            data_get($video, 'status.privacyStatus') !== 'public' ||
            !$this->allowedInSaudiArabia($video)) {
            throw ValidationException::withMessages(['video_id' => 'That video is unavailable for embedding.']);
        }
        return [
            'title' => (string) data_get($video, 'snippet.title'),
            'artist' => (string) data_get($video, 'snippet.channelTitle'),
            'url' => 'https://www.youtube.com/watch?v=' . $id,
            'artwork_url' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
            'platform' => 'youtube',
        ];
    }

    private function allowedInSaudiArabia(array $video): bool
    {
        $allowed = data_get($video, 'contentDetails.regionRestriction.allowed');
        $blocked = data_get($video, 'contentDetails.regionRestriction.blocked');
        if (is_array($allowed) && !in_array('SA', $allowed, true)) return false;
        if (is_array($blocked) && in_array('SA', $blocked, true)) return false;
        return true;
    }

    public function spotifyFromUrl(string $submitted, string $fallbackTitle, string $fallbackArtist): array
    {
        // Parse and reconstruct ONLY an official Spotify URL. No arbitrary fetch/SSRF.
        $parts = parse_url(trim($submitted));
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if (($parts['scheme'] ?? '') !== 'https' || $host !== 'open.spotify.com' ||
            !preg_match('~^/(?:intl-[a-z]{2,5}/)?(track|album|playlist|artist|episode|show)/([A-Za-z0-9]{8,64})/?$~i', $path, $m)) {
            throw ValidationException::withMessages(['spotify_url' => 'Paste a full open.spotify.com track, album, playlist, artist, or podcast link.']);
        }
        $kind = strtolower($m[1]);
        $url = 'https://open.spotify.com/' . $kind . '/' . $m[2];
        $title = $fallbackTitle;
        $artist = $fallbackArtist;
        $art = null;
        try {
            $r = Http::timeout(8)->get('https://open.spotify.com/oembed', ['url' => $url]);
            if ($r->successful()) {
                $title = (string) ($r->json('title') ?: $title);
                $art = $r->json('thumbnail_url');
                if (!is_string($art) || !str_starts_with($art, 'https://i.scdn.co/')) $art = null;
            }
        } catch (\Throwable $e) {
            // Spotify's playlist oEmbed availability can vary. The official embed still works.
            report($e);
        }
        return [
            'title' => mb_substr($title, 0, 120), 'artist' => mb_substr($artist, 0, 120),
            'url' => $url, 'artwork_url' => $art, 'platform' => 'spotify',
        ];
    }
}
