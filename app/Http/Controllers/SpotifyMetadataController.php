<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpotifyMetadataController extends Controller
{
    /**
     * Fetch Spotify metadata using trusted Spotify endpoints only.
     * Never send a request directly to a user-provided URL.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500'],
        ]);

        $parts = parse_url(trim($validated['url']));

        if (
            !is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || strtolower($parts['host'] ?? '') !== 'open.spotify.com'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || !preg_match(
                '~^/(?:intl-[a-z]{2,5}/)?(track|album|playlist|artist|episode|show)/([A-Za-z0-9]{8,64})/?$~i',
                $parts['path'] ?? '',
                $match
            )
        ) {
            return response()->json([
                'message' => 'Please paste a valid Spotify Share URL from open.spotify.com.',
            ], 422);
        }

        $kind = strtolower($match[1]);
        $id = $match[2];

        $canonicalUrl = "https://open.spotify.com/{$kind}/{$id}";

        $title = null;
        $artist = null;
        $source = null;

        /*
         |--------------------------------------------------------------------------
         | 1. Spotify oEmbed
         |--------------------------------------------------------------------------
         | Retrieve public title metadata without API credentials.
         | Artist information is not guaranteed by oEmbed.
         */

        try {
            $oembed = Http::acceptJson()
                ->timeout(8)
                ->get('https://open.spotify.com/oembed', [
                    'url' => $canonicalUrl,
                ]);

            if ($oembed->successful()) {
                $name = $oembed->json('title');

                if (is_string($name) && trim($name) !== '') {
                    $title = mb_substr(trim($name), 0, 120);
                    $source = 'oembed';
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        /*
         |--------------------------------------------------------------------------
         | 2. Spotify Web API
         |--------------------------------------------------------------------------
         | Retrieve the artist and accurate title when API access is configured.
         | Credentials remain on the Laravel server.
         */

        $clientId = trim((string) config('services.spotify.client_id', ''));
        $clientSecret = trim((string) config('services.spotify.client_secret', ''));

        if ($clientId !== '' && $clientSecret !== '') {
            try {
                // Request a Spotify access token.
                $tokenResponse = Http::acceptJson()
                    ->asForm()
                    ->withBasicAuth($clientId, $clientSecret)
                    ->timeout(8)
                    ->post('https://accounts.spotify.com/api/token', [
                        'grant_type' => 'client_credentials',
                    ]);

                $token = $tokenResponse->successful()
                    ? $tokenResponse->json('access_token')
                    : null;

                if (is_string($token) && $token !== '') {
                    // Select Spotify API endpoint based on content type.
                    $apiPath = match ($kind) {
                        'track' => 'tracks',
                        'album' => 'albums',
                        'playlist' => 'playlists',
                        'artist' => 'artists',
                        'episode' => 'episodes',
                        'show' => 'shows',
                    };

                    $item = Http::acceptJson()
                        ->withToken($token)
                        ->timeout(8)
                        ->get("https://api.spotify.com/v1/{$apiPath}/{$id}");

                    if ($item->successful()) {
                        // Retrieve the official title.
                        $name = $item->json('name');

                        if (is_string($name) && trim($name) !== '') {
                            $title = mb_substr(trim($name), 0, 120);
                            $source = 'web_api';
                        }

                        // Retrieve artists or creators.
                        $people = match ($kind) {
                            'track', 'album' => collect(
                                $item->json('artists', [])
                            )
                                ->pluck('name')
                                ->filter(
                                    fn ($value) =>
                                        is_string($value)
                                        && trim($value) !== ''
                                )
                                ->implode(', '),

                            'playlist' => $item->json('owner.display_name'),

                            'episode' => $item->json('show.publisher'),

                            'show' => $item->json('publisher'),

                            'artist' => $item->json('name'),
                        };

                        if (
                            is_string($people)
                            && trim($people) !== ''
                        ) {
                            $artist = mb_substr(
                                trim($people),
                                0,
                                120
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        /*
         |--------------------------------------------------------------------------
         | 3. Response
         |--------------------------------------------------------------------------
         */

        if ($title === null && $artist === null) {
            return response()->json([
                'message' => 'Spotify details are unavailable. You can still enter the song and artist manually.',
            ], 503);
        }

        return response()->json([
            'title' => $title,
            'artist' => $artist,
            'source' => $source,
            'message' => $artist !== null
                ? 'Song and artist found. You can edit the fields before saving.'
                : 'Song title found. Artist details require permitted Spotify Web API access.',
        ]);
    }
}
