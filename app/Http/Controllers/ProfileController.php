<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VisualBoardImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\p{L} ]+$/u'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:280'],
        ]);
        if ($user->email !== $data['email']) $user->email_verified_at = null;
        $user->fill($data)->save();
        return back()->with('profile_success', 'Your profile has been updated.');
    }

    public function avatar(Request $request, VisualBoardImageStorage $images): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']]);
        $user = $request->user();
        $newPath = $images->store($request->file('avatar'));
        $oldPath = $user->avatar_path;
        try {
            $user->update(['avatar_path' => $newPath]);
        } catch (\Throwable $e) {
            $images->delete($newPath);
            throw $e;
        }
        if ($oldPath) $images->delete($oldPath);
        return back()->with('profile_success', 'Profile picture updated.');
    }

    public function removeAvatar(Request $request, VisualBoardImageStorage $images): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;
        $user->update(['avatar_path' => null]);
        if ($oldPath) $images->delete($oldPath);
        return back()->with('profile_success', 'Profile picture removed.');
    }

    public function image(User $user, VisualBoardImageStorage $images): StreamedResponse|RedirectResponse
    {
        abort_unless($user->avatar_path, 404);
        $response = $images->respond($user->avatar_path);
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        return back()->with('profile_success', 'Password changed successfully.');
    }
}
