<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('auto-fills a Spotify title and all artists when Web API access is available', function () {
    // Fake credentials only within this test. Never use or expose real keys.
    config()->set('services.spotify.client_id', 'test-client-id');
    config()->set('services.spotify.client_secret', 'test-client-secret');

    // Return predictable simulated responses for each official Spotify endpoint.
    Http::fake([
        'https://open.spotify.com/oembed*' => Http::response([
            'title' => 'Starboy',
        ], 200),
        'https://accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ], 200),
        'https://api.spotify.com/v1/tracks/*' => Http::response([
            'name' => 'Starboy',
            'artists' => [
                ['name' => 'The Weeknd'],
                ['name' => 'Daft Punk'],
            ],
        ], 200),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('soundtrack.spotify-metadata', [
            'url' => 'https://open.spotify.com/track/7MXVkk9YMctZqd1Srtv4MB?si=abc',
        ]))
        ->assertOk()
        ->assertJsonPath('title', 'Starboy')
        ->assertJsonPath('artist', 'The Weeknd, Daft Punk');

    // Ensure the artist truly came from the Spotify Web API path.
    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'https://accounts.spotify.com/api/token');

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://api.spotify.com/v1/tracks/7MXVkk9YMctZqd1Srtv4MB');
});
