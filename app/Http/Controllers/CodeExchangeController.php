<?php

namespace App\Http\Controllers;

use App\Models\CodeSnippet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CodeExchangeController extends Controller
{
    private const LANGUAGES = ['Python', 'JavaScript', 'PHP', 'Java', 'C#', 'C++', 'SQL', 'HTML/CSS', 'Other'];

    public function index(Request $request): View
    {
        $language = (string) $request->query('language', 'all');
        if ($language !== 'all' && ! in_array($language, self::LANGUAGES, true)) {
            $language = 'all';
        }

        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);
        $onlyMine = $request->boolean('mine') && Auth::check();
        $snippets = CodeSnippet::query()
            ->with(['user', 'comments.user'])
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', Auth::id() ?? 0)])
            ->latest();

        if ($language !== 'all') {
            $snippets->where('language', $language);
        }

        if ($onlyMine) {
            $snippets->where('user_id', Auth::id());
        }

        if ($search !== '') {
            $snippets->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        return view('code-exchange.index', [
            'snippets' => $snippets->paginate(9)->withQueryString(),
            'languages' => self::LANGUAGES,
            'selectedLanguage' => $language,
            'search' => $search,
            'onlyMine' => $onlyMine,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        CodeSnippet::create(['user_id' => Auth::id(), ...$data]);

        return redirect()->route('code-exchange.index')
            ->with('code_success', 'Your code is now part of Code Exchange!');
    }

    public function update(Request $request, CodeSnippet $codeSnippet): RedirectResponse
    {
        $this->requireOwner($codeSnippet);
        $codeSnippet->update($request->validate($this->rules()));

        return redirect()->route('code-exchange.index')
            ->with('code_success', 'Code snippet updated successfully.');
    }

    public function destroy(CodeSnippet $codeSnippet): RedirectResponse
    {
        $this->requireOwner($codeSnippet);
        $codeSnippet->delete();

        return redirect()->route('code-exchange.index')
            ->with('code_success', 'Code snippet deleted.');
    }

    private function requireOwner(CodeSnippet $codeSnippet): void
    {
        abort_unless((int) $codeSnippet->user_id === (int) Auth::id(), 403);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:90'],
            'language' => ['required', Rule::in(self::LANGUAGES)],
            'description' => ['nullable', 'string', 'max:700'],
            'code' => ['required', 'string', 'min:2', 'max:8000'],
        ];
    }
}
