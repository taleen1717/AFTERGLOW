<?php

namespace App\Http\Controllers;

use App\Models\VisualBoard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisualBoardController extends Controller
{
    private const MOODS = ['Dreamy', 'Night Drive', 'Cyber', 'Love', 'Chaos', 'Deep Thoughts'];

    public function index(Request $request): View
    {
        $mood = (string) $request->query('mood', 'all');
        if ($mood !== 'all' && ! in_array($mood, self::MOODS, true)) {
            $mood = 'all';
        }

        $mine = $request->boolean('mine') && Auth::check();
        $query = VisualBoard::query()->with('user')->latest();

        if ($mood !== 'all') {
            $query->where('mood', $mood);
        }
        if ($mine) {
            $query->where('user_id', Auth::id());
        }

        return view('boards.index', [
            'boards' => $query->paginate(12)->withQueryString(),
            'moods' => self::MOODS,
            'selectedMood' => $mood,
            'onlyMine' => $mine,
        ]);
    }

    public function image(VisualBoard $visualBoard): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($visualBoard->image_path), 404);

        // Public gallery images are served via a Laravel route, so Windows users
        // do not need to create a public/storage symbolic link.
        return Storage::disk('public')->response(
            $visualBoard->image_path,
            null,
            ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']
        );
    }

    public function store(Request $request): RedirectResponse
    {


        $data = $request->validate($this->rules(true));

        $imagePath = $request->file('image')->store('visual-boards', 'public');
        abort_if($imagePath === false, 500, 'Unable to store your image.');

        VisualBoard::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'mood' => $data['mood'],
            'image_path' => $imagePath,
        ]);

        return redirect()->route('boards.index')->with('board_success', 'Your artwork is now on Visual Boards!');
    }

    public function update(Request $request, VisualBoard $visualBoard): RedirectResponse
    {
        $this->checkOwner($visualBoard);
        $data = $request->validate($this->rules(false));
        $previousImage = $visualBoard->image_path;

        if ($request->hasFile('image')) {
            $nextImage = $request->file('image')->store('visual-boards', 'public');
            abort_if($nextImage === false, 500, 'Unable to store your replacement image.');
            $data['image_path'] = $nextImage;
        }
        unset($data['image']);

        $visualBoard->update($data);
        if (isset($nextImage)) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('boards.index')->with('board_success', 'Artwork updated successfully.');
    }

    public function destroy(VisualBoard $visualBoard): RedirectResponse
    {
        $this->checkOwner($visualBoard);
        $imagePath = $visualBoard->image_path;
        $visualBoard->delete();
        Storage::disk('public')->delete($imagePath);

        return redirect()->route('boards.index')->with('board_success', 'Artwork removed from your gallery.');
    }

    private function checkOwner(VisualBoard $visualBoard): void
    {
        abort_unless((int) $visualBoard->user_id === (int) Auth::id(), 403);
    }

    private function rules(bool $requiredImage): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:90'],
            'description' => ['nullable', 'string', 'max:500'],
            'mood' => ['required', Rule::in(self::MOODS)],
            'image' => [$requiredImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }
}
