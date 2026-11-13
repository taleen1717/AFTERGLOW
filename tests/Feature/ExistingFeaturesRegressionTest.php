<?php

use App\Models\User;
use App\Models\VisualBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates edits and deletes an owned quote while rejecting edits by others', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $data = [
        'content' => 'Keep creating', 'mood' => 'Dreamy', 'card_style' => 1,
        'text_font' => 'modern', 'text_color' => 'auto',
    ];
    $this->actingAs($owner)->post('/', $data)->assertRedirect();
    $quote = $owner->quotes()->firstOrFail();
    $this->actingAs($stranger)->put(route('edit_quote', $quote), [...$data, 'content' => 'Not allowed'])->assertForbidden();
    $this->actingAs($owner)->put(route('edit_quote', $quote), [...$data, 'content' => 'New beginning'])->assertRedirect(route('home'));
    $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'content' => 'New beginning']);
    $this->actingAs($owner)->delete(route('delete_quote', $quote))->assertRedirect();
    $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
});

it('rejects invalid quote lengths', function () {
    $member = User::factory()->create();
    $this->actingAs($member)->post('/', [
        'content' => 'No', 'mood' => 'Dreamy', 'card_style' => 1, 'text_font' => 'modern', 'text_color' => 'auto',
    ])->assertSessionHasErrors('content');
});

it('uploads a visual board and restricts deletion to its owner', function () {
    config(['afterglow_visual_storage.driver' => 'public']);
    Storage::fake('public');
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $this->actingAs($owner)->post(route('boards.store'), [
        'title' => 'Neon city', 'description' => 'A city at night', 'mood' => 'Cyber',
        'image' => UploadedFile::fake()->image('neon.png'),
    ])->assertRedirect();
    $board = VisualBoard::firstOrFail();
    Storage::disk('public')->assertExists($board->image_path);
    $this->actingAs($stranger)->delete(route('boards.destroy', $board))->assertForbidden();
    $this->actingAs($owner)->delete(route('boards.destroy', $board))->assertRedirect();
    Storage::disk('public')->assertMissing($board->image_path);
});

it('saves a Spotify song privately unless public sharing is selected', function () {
    $owner = User::factory()->create();
    Http::fake(['open.spotify.com/oembed*' => Http::response(['title' => 'An example track', 'thumbnail_url' => null], 200)]);
    $data = [
        'platform' => 'spotify', 'spotify_url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
        'title' => 'Fallback title', 'artist' => 'Artist', 'mood' => 'Night Drive', 'is_public' => 0,
    ];
    $this->actingAs($owner)->post(route('soundtrack.store'), $data)->assertRedirect();
    $song = $owner->favoriteSongs()->firstOrFail();
    expect($song->is_public)->toBeFalse();
    $this->get(route('soundtrack.discover'))->assertDontSee('An example track');
    $this->actingAs($owner)->put(route('soundtrack.update', $song), [
        'title' => 'An example track', 'artist' => 'Artist', 'mood' => 'Night Drive', 'is_public' => 1,
    ])->assertRedirect();
    $this->get(route('soundtrack.discover'))->assertSee('An example track');
});
