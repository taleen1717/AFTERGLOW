<?php
namespace App\Http\Requests;

use App\Models\FavoriteSong;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FavoriteSongRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $base = [
            'mood' => ['required', Rule::in(FavoriteSong::MOODS)],
            'quote_id' => ['nullable', 'integer', Rule::exists('quotes', 'id')->where('user_id', $this->user()->id)],
        ];
        if (!$this->isMethod('post')) {
            return $base + [
                'title' => ['required', 'string', 'max:120'],
                'artist' => ['required', 'string', 'max:120'],
            ];
        }
        return $base + [
            'platform' => ['required', Rule::in(['youtube', 'spotify'])],
            'video_id' => ['required_if:platform,youtube', 'nullable', 'string', 'regex:/^[A-Za-z0-9_-]{11}$/'],
            'spotify_url' => ['required_if:platform,spotify', 'nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:120'],
            'artist' => ['nullable', 'string', 'max:120'],
        ];
    }
}
