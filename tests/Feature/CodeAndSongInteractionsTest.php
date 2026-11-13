<?php

use App\Models\CodeComment;
use App\Models\CodeSnippet;
use App\Models\FavoriteSong;
use App\Models\SongComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function demoSnippet(User $owner): CodeSnippet
{
    return CodeSnippet::create([
        'user_id' => $owner->id, 'title' => 'Simple algorithm', 'language' => 'Python',
        'description' => 'Small tip', 'code' => 'print("hello")',
    ]);
}

function demoSong(User $owner, bool $public): FavoriteSong
{
    return $owner->favoriteSongs()->create([
        'title' => 'A gentle track', 'artist' => 'Test Artist',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'mood' => 'Night Drive', 'is_public' => $public,
    ]);
}

it('allows a snippet author and visitors to like, and toggles without duplicates', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $snippet = demoSnippet($owner);
    $this->actingAs($owner)->post(route('code-exchange.like', $snippet))->assertRedirect();
    $this->actingAs($visitor)->post(route('code-exchange.like', $snippet))->assertRedirect();
    $this->assertDatabaseCount('code_likes', 2);
    $this->actingAs($visitor)->post(route('code-exchange.like', $snippet))->assertRedirect();
    $this->assertDatabaseCount('code_likes', 1);
    $this->get(route('code-exchange.index'))->assertOk()->assertSee('1 Likes');
});

it('accepts code comments, validates length, and protects deletion by author', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $snippet = demoSnippet($owner);
    $this->actingAs($visitor)->post(route('code-exchange.comments.store', $snippet), ['body' => 'Nice snippet!'])->assertRedirect();
    $this->get(route('code-exchange.index'))->assertSee('Nice snippet!');
    $comment = CodeComment::firstOrFail();
    $this->actingAs($owner)->delete(route('code-exchange.comments.destroy', $comment))->assertForbidden();
    $this->actingAs($visitor)->post(route('code-exchange.comments.store', $snippet), ['body' => str_repeat('x', 501)])->assertSessionHasErrors('body');
    $this->actingAs($visitor)->delete(route('code-exchange.comments.destroy', $comment))->assertRedirect();
    $this->assertDatabaseCount('code_comments', 0);
});

it('requires sign in for code and song reactions', function () {
    $owner = User::factory()->create();
    $snippet = demoSnippet($owner);
    $song = demoSong($owner, true);
    $this->post(route('code-exchange.like', $snippet))->assertRedirect(route('login'));
    $this->post(route('code-exchange.comments.store', $snippet), ['body' => 'Hello'])->assertRedirect(route('login'));
    $this->post(route('soundtrack.public.like', $song))->assertRedirect(route('login'));
    $this->post(route('soundtrack.public.comments.store', $song), ['body' => 'Hello'])->assertRedirect(route('login'));
});

it('shows likes and comments on public songs and allows owners to like', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $song = demoSong($owner, true);
    $this->actingAs($owner)->post(route('soundtrack.public.like', $song))->assertRedirect();
    $this->actingAs($visitor)->post(route('soundtrack.public.like', $song))->assertRedirect();
    $this->assertDatabaseCount('song_likes', 2);
    $this->actingAs($visitor)->post(route('soundtrack.public.comments.store', $song), ['body' => 'Beautiful song'])->assertRedirect();
    $this->get(route('soundtrack.discover'))->assertOk()->assertSee('Beautiful song')->assertSee('2 Likes');
    $this->actingAs($owner)->get(route('soundtrack.index'))->assertOk()->assertSee('Beautiful song');
    $this->actingAs($visitor)->post(route('soundtrack.public.like', $song))->assertRedirect();
    $this->assertDatabaseCount('song_likes', 1);
});

it('never exposes private songs or permits likes or comments on them', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $song = demoSong($owner, false);
    $this->actingAs($visitor)->post(route('soundtrack.public.like', $song))->assertNotFound();
    $this->actingAs($visitor)->post(route('soundtrack.public.comments.store', $song), ['body' => 'Guess'])->assertNotFound();
    $this->actingAs($owner)->post(route('soundtrack.public.like', $song))->assertNotFound();
    $this->get(route('soundtrack.discover'))->assertDontSee('A gentle track');
    $this->assertDatabaseCount('song_likes', 0);
    $this->assertDatabaseCount('song_comments', 0);
});

it('hides existing song reactions after switching to private', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $song = demoSong($owner, true);
    $this->actingAs($visitor)->post(route('soundtrack.public.comments.store', $song), ['body' => 'Loved it'])->assertRedirect();
    $song->update(['is_public' => false]);
    $this->get(route('soundtrack.discover'))->assertDontSee('Loved it')->assertDontSee('A gentle track');
    $this->actingAs($visitor)->post(route('soundtrack.public.like', $song))->assertNotFound();
    $this->actingAs($owner)->get(route('soundtrack.index'))->assertDontSee('Loved it');
});

it('only permits the author to delete a song comment', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $song = demoSong($owner, true);
    $this->actingAs($visitor)->post(route('soundtrack.public.comments.store', $song), ['body' => 'Great track'])->assertRedirect();
    $comment = SongComment::firstOrFail();
    $this->actingAs($owner)->delete(route('soundtrack.public.comments.destroy', $comment))->assertForbidden();
    $this->actingAs($visitor)->delete(route('soundtrack.public.comments.destroy', $comment))->assertRedirect();
    $this->assertDatabaseCount('song_comments', 0);
});

it('removes likes and comments when a snippet or song is deleted', function () {
    $owner = User::factory()->create();
    $snippet = demoSnippet($owner);
    $song = demoSong($owner, true);
    $this->actingAs($owner)->post(route('code-exchange.like', $snippet));
    $this->actingAs($owner)->post(route('code-exchange.comments.store', $snippet), ['body' => 'My code']);
    $this->actingAs($owner)->post(route('soundtrack.public.like', $song));
    $this->actingAs($owner)->post(route('soundtrack.public.comments.store', $song), ['body' => 'My music']);
    $snippet->delete();
    $song->delete();
    $this->assertDatabaseCount('code_likes', 0);
    $this->assertDatabaseCount('code_comments', 0);
    $this->assertDatabaseCount('song_likes', 0);
    $this->assertDatabaseCount('song_comments', 0);
});
