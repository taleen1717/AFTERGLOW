<?php

use App\Services\AfterglowMusic;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('saves a Spotify artwork URL provided by the public oEmbed endpoint', function () {
    config()->set('services.spotify.client_id', null);
    config()->set('services.spotify.client_secret', null);

    Http::fake([
        'open.spotify.com/oembed*' => Http::response([
            'title' => 'A Sample Song',
            'thumbnail_url' => 'https://image-cdn-ak.spotifycdn.com/image/spotify-cover-123',
        ], 200),
    ]);

    $song = app(AfterglowMusic::class)->spotifyFromUrl(
        'https://open.spotify.com/track/5QO79kh1waicV47BqGRL3g?si=abc',
        'A Sample Song',
        'A Sample Artist'
    );

    expect($song['artwork_url'])->toBe('https://image-cdn-ak.spotifycdn.com/image/spotify-cover-123')
        ->and($song['title'])->toBe('A Sample Song')
        ->and($song['artist'])->toBe('A Sample Artist');
});

it('prefers official Spotify album artwork when Web API track metadata is available', function () {
    config()->set('services.spotify.client_id', 'test-id');
    config()->set('services.spotify.client_secret', 'test-secret');
    Cache::forget('ag:spotify:token:' . hash('sha256', 'test-id'));

    Http::fake([
        'open.spotify.com/oembed*' => Http::response([
            'title' => 'Demo Title',
            'thumbnail_url' => 'https://i.scdn.co/image/oembedcover',
        ], 200),
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'fake-token', 'token_type' => 'Bearer',
        ], 200),
        'api.spotify.com/v1/tracks/*' => Http::response([
            'name' => 'Demo Title',
            'artists' => [['name' => 'Artist A'], ['name' => 'Artist B']],
            'album' => ['images' => [['url' => 'https://i.scdn.co/image/albumcover']]],
        ], 200),
    ]);

    $metadata = app(AfterglowMusic::class)->spotifyMetadata(
        'https://open.spotify.com/track/5QO79kh1waicV47BqGRL3g'
    );

    expect($metadata['artist'])->toBe('Artist A, Artist B')
        ->and($metadata['artwork_url'])->toBe('https://i.scdn.co/image/albumcover');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/tracks/'));
});

it('does not accept thumbnail URLs from untrusted domains', function () {
    config()->set('services.spotify.client_id', null);
    config()->set('services.spotify.client_secret', null);
    Http::fake([
        'open.spotify.com/oembed*' => Http::response([
            'title' => 'Demo',
            'thumbnail_url' => 'https://not-spotify.example/image/fake.png',
        ], 200),
    ]);

    $metadata = app(AfterglowMusic::class)->spotifyMetadata('https://open.spotify.com/track/5QO79kh1waicV47BqGRL3g');
    expect($metadata['artwork_url'])->toBeNull();
});
