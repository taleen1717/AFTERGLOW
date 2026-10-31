<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:3', 'max:155'],
            'mood' => ['required', 'in:Uncategorized,Dreamy,Night Drive,Cyber,Love,Chaos,Deep Thoughts'],
            'card_style' => ['required', 'integer', 'between:1,3'],
            'text_font' => ['required', 'in:modern,rounded,classic,elegant,typewriter,arabic'],
            'text_color' => ['required', 'regex:/^(?:auto|navy|plum|rose|teal|cobalt|white|cream|#[0-9A-Fa-f]{6})$/'],
        ];
    }
}
