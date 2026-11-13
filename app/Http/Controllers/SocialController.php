<?php

namespace App\Http\Controllers;

use App\Models\BoardComment;
use App\Models\CodeComment;
use App\Models\CodeSnippet;
use App\Models\FavoriteSong;
use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\SongComment;
use App\Models\VisualBoard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function quoteLike(Request $request, Quote $quote): RedirectResponse
    {
        return $this->toggleLike($request, $quote);
    }

    public function boardLike(Request $request, VisualBoard $visualBoard): RedirectResponse
    {
        return $this->toggleLike($request, $visualBoard);
    }

    public function codeLike(Request $request, CodeSnippet $codeSnippet): RedirectResponse
    {
        return $this->toggleLike($request, $codeSnippet);
    }

    public function songLike(Request $request, FavoriteSong $favoriteSong): RedirectResponse
    {
        $this->requirePublicSong($favoriteSong);
        return $this->toggleLike($request, $favoriteSong);
    }

    private function toggleLike(Request $request, Model $post): RedirectResponse
    {
        // One like per user per post; clicking again removes it. Owners may like their own posts.
        $like = $post->likes()->where('user_id', $request->user()->id)->first();
        if ($like) {
            $like->delete();
        } else {
            $post->likes()->firstOrCreate(['user_id' => $request->user()->id]);
        }
        return back();
    }

    public function quoteComment(Request $request, Quote $quote): RedirectResponse
    {
        return $this->addComment($request, $quote);
    }

    public function boardComment(Request $request, VisualBoard $visualBoard): RedirectResponse
    {
        return $this->addComment($request, $visualBoard);
    }

    public function codeComment(Request $request, CodeSnippet $codeSnippet): RedirectResponse
    {
        return $this->addComment($request, $codeSnippet);
    }

    public function songComment(Request $request, FavoriteSong $favoriteSong): RedirectResponse
    {
        $this->requirePublicSong($favoriteSong);
        return $this->addComment($request, $favoriteSong);
    }

    private function addComment(Request $request, Model $post): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:500']]);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        return back();
    }

    public function deleteQuoteComment(Request $request, QuoteComment $comment): RedirectResponse
    {
        return $this->deleteOwnComment($request, $comment);
    }

    public function deleteBoardComment(Request $request, BoardComment $comment): RedirectResponse
    {
        return $this->deleteOwnComment($request, $comment);
    }

    public function deleteCodeComment(Request $request, CodeComment $comment): RedirectResponse
    {
        return $this->deleteOwnComment($request, $comment);
    }

    public function deleteSongComment(Request $request, SongComment $comment): RedirectResponse
    {
        return $this->deleteOwnComment($request, $comment);
    }

    private function deleteOwnComment(Request $request, Model $comment): RedirectResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        $comment->delete();
        return back();
    }

    private function requirePublicSong(FavoriteSong $song): void
    {
        // Return 404 instead of 403: do not reveal the existence of private library entries.
        abort_unless($song->is_public, 404);
    }
}
