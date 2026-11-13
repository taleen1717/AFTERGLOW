<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('uses public oEmbed for titles but does not invent artist names', function () {
    $this->actingAs(User::factory()->create());
    config()->set('services.spotify.client_id', '');
    config()->set('services.spotify.client_secret', '');

    Http::fake([
        'https://open.spotify.com/oembed*' => Http::response(['title' => 'Example Song'], 200),
    ]);

    $this->getJson(route('soundtrack.spotify-metadata', [
        'url' => 'https://open.spotify.com/track/7MXVkk9YMctZqd1Srtv4MB?si=share',
    ]))->assertOk()->assertJsonPath('title', 'Example Song')->assertJsonPath('artist', null);

    Http::assertSentCount(1);
});

it('fetches metadata for many Spotify song links including multiple artists', function () {
    // Credentials are intentionally fake. The network below is completely mocked.
    $this->actingAs(User::factory()->create());
    config()->set('services.spotify.client_id', 'unit-test-client');
    config()->set('services.spotify.client_secret', 'unit-test-secret');
    expect(config('services.spotify.client_id'))->toBe('unit-test-client');
    expect(config('services.spotify.client_secret'))->toBe('unit-test-secret');

    $fixtures = [
        '7MXVkk9YMctZqd1Srtv4MB' => ['title' => 'Song One', 'artists' => ['Artist A', 'Artist B']],
        '0VjIjW4GlUZAMYd2vXMi3b' => ['title' => 'Song Two', 'artists' => ['Artist C']],
        '39LLxExYz6ewLAcYrzQQyP' => ['title' => 'Song Three', 'artists' => ['Artist D', 'Artist E']],
    ];

    Http::fake(function ($request) use ($fixtures) {
        $url = $request->url();
        if (str_starts_with($url, 'https://open.spotify.com/oembed?')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $itemId = basename((string) parse_url($query['url'] ?? '', PHP_URL_PATH));
            return Http::response(['title' => $fixtures[$itemId]['title'] ?? 'Unknown'], 200);
        }
        if ($url === 'https://accounts.spotify.com/api/token') {
            return Http::response(['access_token' => 'fake-token'], 200);
        }
        if (str_starts_with($url, 'https://api.spotify.com/v1/tracks/')) {
            $itemId = basename($url);
            $fixture = $fixtures[$itemId] ?? null;
            if ($fixture === null) {
                return Http::response(['error' => 'Unknown fixture'], 404);
            }
            return Http::response([
                'name' => $fixture['title'],
                'artists' => array_map(fn ($artist) => ['name' => $artist], $fixture['artists']),
            ], 200);
        }
        return Http::response(['error' => 'Unexpected request in test'], 418);
    });

    foreach ($fixtures as $id => $fixture) {
        $this->getJson(route('soundtrack.spotify-metadata', [
            'url' => "https://open.spotify.com/track/{$id}",
        ]))->assertOk()
            ->assertJsonPath('title', $fixture['title'])
            ->assertJsonPath('artist', implode(', ', $fixture['artists']));
    }

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://accounts.spotify.com/api/token';
    });
    Http::assertSent(function ($request) use ($fixtures) {
        return $request->method() === 'GET'
            && $request->url() === 'https://api.spotify.com/v1/tracks/'.array_key_first($fixtures);
    });
});

it('rejects URLs outside Spotify without sending HTTP requests', function () {
    $this->actingAs(User::factory()->create());
    Http::fake();

    $this->getJson(route('soundtrack.spotify-metadata', [
        'url' => 'https://example.com/track/7MXVkk9YMctZqd1Srtv4MB',
    ]))->assertUnprocessable();

    Http::assertNothingSent();
});
