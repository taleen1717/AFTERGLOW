<?php

use App\Models\FavoriteSong;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('offers embedded YouTube and Spotify players for public community songs only', function () {
    $owner = User::factory()->create();
    $owner->favoriteSongs()->create([
        'title' => 'A Public Video', 'artist' => 'Demo Artist',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'platform' => 'youtube', 'mood' => 'Night Drive', 'is_public' => true,
    ]);
    $owner->favoriteSongs()->create([
        'title' => 'A Public Spotify Track', 'artist' => 'Demo Artist',
        'url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
        'platform' => 'spotify', 'mood' => 'Icy Dream', 'is_public' => true,
    ]);
    $owner->favoriteSongs()->create([
        'title' => 'A Private Track', 'artist' => 'Demo Artist',
        'url' => 'https://www.youtube.com/watch?v=jfKfPfyJRdk',
        'platform' => 'youtube', 'mood' => 'Soft Hours', 'is_public' => false,
    ]);

    $this->get(route('soundtrack.discover'))->assertOk()
        ->assertSee('Play here')
        ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('open.spotify.com/embed/track/4uLU6hMCjMI75M1A2tKUQC', false)
        ->assertDontSee('A Private Track')
        ->assertDontSee('jfKfPfyJRdk');
});
