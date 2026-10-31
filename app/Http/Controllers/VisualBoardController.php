<?php

namespace App\Http\Controllers;

use App\Models\VisualBoard;
use App\Services\VisualBoardImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function image(VisualBoard $visualBoard, VisualBoardImageStorage $images): StreamedResponse|RedirectResponse
    {
        // Local images continue to work during development; hosted images redirect
        // to Cloudinary's CDN and remain available after Render restarts.
        return $images->respond($visualBoard->image_path);
    }

    public function store(Request $request, VisualBoardImageStorage $images): RedirectResponse
    {
        $data = $request->validate($this->rules(true));

        $imagePath = $images->store($request->file('image'));

        try {
            VisualBoard::create([
                'user_id' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'mood' => $data['mood'],
                'image_path' => $imagePath,
            ]);
        } catch (\Throwable $e) {
            // Avoid leaving a cloud image behind if saving the database row fails.
            $images->delete($imagePath);
            throw $e;
        }

        return redirect()->route('boards.index')->with('board_success', 'Your artwork is now on Visual Boards!');
    }

    public function update(Request $request, VisualBoard $visualBoard, VisualBoardImageStorage $images): RedirectResponse
    {
        $this->checkOwner($visualBoard);
        $data = $request->validate($this->rules(false));
        $previousImage = $visualBoard->image_path;

        if ($request->hasFile('image')) {
            $nextImage = $images->store($request->file('image'));
            $data['image_path'] = $nextImage;
        }
        unset($data['image']);

        try {
            $visualBoard->update($data);
        } catch (\Throwable $e) {
            if (isset($nextImage)) {
                $images->delete($nextImage);
            }
            throw $e;
        }

        if (isset($nextImage)) {
            $images->delete($previousImage);
        }

        return redirect()->route('boards.index')->with('board_success', 'Artwork updated successfully.');
    }

    public function destroy(VisualBoard $visualBoard, VisualBoardImageStorage $images): RedirectResponse
    {
        $this->checkOwner($visualBoard);
        $imagePath = $visualBoard->image_path;
        $visualBoard->delete();
        $images->delete($imagePath);

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
