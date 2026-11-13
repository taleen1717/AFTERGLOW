<?php

use App\Models\BoardComment;
use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\User;
use App\Models\VisualBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a member like and unlike a quote once, including their own', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $quote = $owner->quotes()->create(['content' => 'A small dream']);

    $this->actingAs($visitor)->post(route('quotes.like', $quote))->assertRedirect();
    $this->assertDatabaseCount('quote_likes', 1);
    $this->actingAs($visitor)->post(route('quotes.like', $quote))->assertRedirect();
    $this->assertDatabaseCount('quote_likes', 0);
    $this->actingAs($owner)->post(route('quotes.like', $quote))->assertRedirect();
    $this->assertDatabaseCount('quote_likes', 1);
});

it('requires authentication to like or comment', function () {
    $quote = User::factory()->create()->quotes()->create(['content' => 'Stay curious']);
    $this->post(route('quotes.like', $quote))->assertRedirect(route('login'));
    $this->post(route('quotes.comments.store', $quote), ['body' => 'Love it'])->assertRedirect(route('login'));
});

it('allows comments but only their authors can delete them', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $quote = $owner->quotes()->create(['content' => 'Every day is a start']);
    $this->actingAs($visitor)->post(route('quotes.comments.store', $quote), ['body' => 'Beautiful thought'])->assertRedirect();
    $comment = QuoteComment::firstOrFail();
    $this->actingAs($owner)->delete(route('quotes.comments.destroy', $comment))->assertForbidden();
    $this->actingAs($visitor)->delete(route('quotes.comments.destroy', $comment))->assertRedirect();
    $this->assertDatabaseCount('quote_comments', 0);
});

it('validates comment length', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $quote = $owner->quotes()->create(['content' => 'Make it real']);
    $this->actingAs($visitor)->post(route('quotes.comments.store', $quote), ['body' => str_repeat('x', 501)])->assertSessionHasErrors('body');
    $this->assertDatabaseCount('quote_comments', 0);
});

it('supports likes and comments on visual boards with owner-only deletion', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $board = VisualBoard::create(['user_id' => $owner->id, 'title' => 'Night city', 'mood' => 'Cyber', 'image_path' => 'visual-boards/example.png']);
    $this->actingAs($visitor)->post(route('boards.like', $board))->assertRedirect();
    $this->assertDatabaseCount('board_likes', 1);
    $this->actingAs($visitor)->post(route('boards.comments.store', $board), ['body' => 'Love these colors'])->assertRedirect();
    $comment = BoardComment::firstOrFail();
    $this->actingAs($owner)->delete(route('boards.comments.destroy', $comment))->assertForbidden();
    $this->actingAs($visitor)->delete(route('boards.comments.destroy', $comment))->assertRedirect();
    $this->actingAs($owner)->post(route('boards.like', $board))->assertRedirect();
    $this->assertDatabaseCount('board_likes', 2);
});

it('deletes social activity when its quote is deleted', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $quote = $owner->quotes()->create(['content' => 'Keep shining']);
    $this->actingAs($visitor)->post(route('quotes.like', $quote));
    $this->actingAs($visitor)->post(route('quotes.comments.store', $quote), ['body' => 'Yes!']);
    $quote->delete();
    $this->assertDatabaseCount('quote_likes', 0);
    $this->assertDatabaseCount('quote_comments', 0);
});
