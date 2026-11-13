<?php
namespace App\Http\Controllers;

use App\Services\AfterglowMusic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class MusicDiscoveryController extends Controller
{
    public function spotify(Request $request, AfterglowMusic $music): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'string', 'max:500']]);
        try {
            return response()->json($music->spotifyMetadata($data['url']));
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Spotify metadata is temporarily unavailable. You may type the details manually.'], 503);
        }
    }

    public function youtube(Request $request, AfterglowMusic $music): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:140']]);
        try {
            return response()->json(['results' => $music->youtubeSearch($data['q'])]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Search is temporarily unavailable.'], 503);
        }
    }
}
