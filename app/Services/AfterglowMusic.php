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

    /**
     * Accept official Spotify CDN images only. The URL comes from Spotify APIs,
     * not from a user-controlled image field.
     */
    private function spotifyArtworkUrl(mixed $candidate): ?string
    {
        if (!is_string($candidate) || strlen($candidate) > 2048) {
            return null;
        }
        $parts = parse_url($candidate);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        if ($host === 'i.scdn.co' || $host === 'mosaic.scdn.co'
            || preg_match('/^image-cdn-[a-z0-9-]+\.spotifycdn\.com$/', $host)) {
            return $candidate;
        }
        return null;
    }

    /**
     * Public title and artwork via oEmbed; accurate artist and album artwork
     * via Spotify Web API when server credentials are configured.
     */
    public function spotifyMetadata(string $submitted): array
    {
        $parts = parse_url(trim($submitted));
        if (!is_array($parts)) {
            throw ValidationException::withMessages(['spotify_url' => 'Paste a valid Spotify Share URL.']);
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if (strtolower($parts['scheme'] ?? '') !== 'https' || $host !== 'open.spotify.com'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || !preg_match('~^/(?:intl-[a-z]{2,5}/)?(track|album|playlist|artist|episode|show)/([A-Za-z0-9]{8,64})/?$~i', $path, $matches)) {
            throw ValidationException::withMessages([
                'spotify_url' => 'Paste a full open.spotify.com track, album, playlist, artist, or podcast link.',
            ]);
        }

        $kind = strtolower($matches[1]);
        $id = $matches[2];
        $url = 'https://open.spotify.com/' . $kind . '/' . $id;
        $result = [
            'url' => $url, 'kind' => $kind, 'title' => null,
            'artist' => null, 'artwork_url' => null,
        ];

        // Spotify oEmbed usually supplies public title/thumbnail with no API credentials.
        try {
            $response = Http::timeout(8)->get('https://open.spotify.com/oembed', ['url' => $url]);
            if ($response->successful()) {
                $title = $response->json('title');
                if (is_string($title) && trim($title) !== '') {
                    $result['title'] = mb_substr(trim($title), 0, 120);
                }
                // Accept the official CDN domains Spotify may use for artwork.
                $result['artwork_url'] = $this->spotifyArtworkUrl($response->json('thumbnail_url'));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        // IMPORTANT: use the same config keys as SpotifyMetadataController.
        $clientId = trim((string) config('services.spotify.client_id', ''));
        $clientSecret = trim((string) config('services.spotify.client_secret', ''));
        if ($kind === 'track' && $clientId !== '' && $clientSecret !== '') {
            try {
                $token = Cache::remember(
                    'ag:spotify:token:' . hash('sha256', $clientId),
                    now()->addMinutes(45),
                    function () use ($clientId, $clientSecret): ?string {
                        $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)
                            ->timeout(8)->post('https://accounts.spotify.com/api/token', [
                                'grant_type' => 'client_credentials',
                            ]);
                        return $response->successful() ? $response->json('access_token') : null;
                    }
                );
                if (is_string($token) && $token !== '') {
                    $response = Http::withToken($token)->timeout(8)
                        ->get('https://api.spotify.com/v1/tracks/' . $id);
                    if ($response->successful()) {
                        $name = $response->json('name');
                        $artists = collect($response->json('artists', []))
                            ->pluck('name')->filter(fn ($name) => is_string($name) && trim($name) !== '')
                            ->implode(', ');
                        if (is_string($name) && trim($name) !== '') {
                            $result['title'] = mb_substr(trim($name), 0, 120);
                        }
                        if ($artists !== '') {
                            $result['artist'] = mb_substr($artists, 0, 120);
                        }
                        // Prefer the track's official album cover from the Web API.
                        $apiArtwork = $this->spotifyArtworkUrl($response->json('album.images.0.url'));
                        if ($apiArtwork !== null) {
                            $result['artwork_url'] = $apiArtwork;
                        }
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    public function spotifyFromUrl(string $submitted, string $fallbackTitle, string $fallbackArtist): array
    {
        $metadata = $this->spotifyMetadata($submitted);
        // Respect user-entered or auto-filled values, including their corrections.
        $title = trim($fallbackTitle) !== '' ? $fallbackTitle : ($metadata['title'] ?: 'Spotify favorite');
        $artist = trim($fallbackArtist) !== '' ? $fallbackArtist : ($metadata['artist'] ?: 'Spotify');
        return [
            'title' => mb_substr($title, 0, 120),
            'artist' => mb_substr($artist, 0, 120),
            'url' => $metadata['url'],
            'artwork_url' => $metadata['artwork_url'],
            'platform' => 'spotify',
        ];
    }
}
