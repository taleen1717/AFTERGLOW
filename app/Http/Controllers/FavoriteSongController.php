<?php
namespace App\Http\Controllers;

use App\Http\Requests\FavoriteSongRequest;
use App\Models\FavoriteSong;
use App\Models\Quote;
use App\Services\AfterglowMusic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriteSongController extends Controller
{
    public function index(Request $request): View
    {
        return view('soundtrack.index', [
            'songs' => FavoriteSong::where('user_id', $request->user()->id)->with('quote:id,content')
                ->orderByDesc('is_on_repeat')->latest()->get(),
            'quotes' => Quote::where('user_id', $request->user()->id)->latest()->get(['id', 'content']),
            'moods' => FavoriteSong::MOODS,
        ]);
    }

    public function store(FavoriteSongRequest $request, AfterglowMusic $music): RedirectResponse
    {
        $data = $request->validated();
        $track = $data['platform'] === 'youtube'
            ? $music->youtubeVideo($data['video_id'])
            : $music->spotifyFromUrl($data['spotify_url'], $data['title'] ?: 'Spotify favorite', $data['artist'] ?: 'Spotify');
        $request->user()->favoriteSongs()->create($track + [
            'mood' => $data['mood'], 'quote_id' => $data['quote_id'] ?? null,
        ]);
        return redirect()->route('soundtrack.index')->with('soundtrack_success', 'Saved to My Soundtrack!');
    }

    public function update(FavoriteSongRequest $request, FavoriteSong $favoriteSong): RedirectResponse
    {
        $this->owner($request, $favoriteSong);
        $favoriteSong->update($request->safe()->only(['title', 'artist', 'mood', 'quote_id']));
        return redirect()->route('soundtrack.index')->with('soundtrack_success', 'Song updated!');
    }

    public function destroy(Request $request, FavoriteSong $favoriteSong): RedirectResponse
    {
        $this->owner($request, $favoriteSong);
        $favoriteSong->delete();
        return redirect()->route('soundtrack.index')->with('soundtrack_success', 'Removed from library.');
    }

    public function repeat(Request $request, FavoriteSong $favoriteSong): RedirectResponse
    {
        $this->owner($request, $favoriteSong);
        $already = (bool) $favoriteSong->is_on_repeat;
        DB::transaction(function () use ($request, $favoriteSong, $already): void {
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            FavoriteSong::where('user_id', $request->user()->id)->update(['is_on_repeat' => false]);
            if (!$already) $favoriteSong->update(['is_on_repeat' => true]);
        });
        return redirect()->route('soundtrack.index')->with('soundtrack_success', 'Currently on Repeat updated!');
    }

    private function owner(Request $request, FavoriteSong $song): void
    {
        abort_unless((int) $song->user_id === (int) $request->user()->id, 403);
    }
}
