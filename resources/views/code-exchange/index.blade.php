@extends('Layout.app')

@section('title', 'Code Exchange — AFTERGLOW')

@section('content')
<link rel="stylesheet" href="{{ asset('afterglow/code-exchange.css') }}?v=1">
<script defer src="{{ asset('afterglow/code-exchange.js') }}?v=1"></script>

<div class="ag-ce-page">
    <div class="ag-ce-wrap">
        <div class="ag-ce-topline">
            <a href="{{ route('home') }}">← Back to AFTERGLOW</a>
            <span>CODE · IDEAS · SMALL DISCOVERIES</span>
        </div>
        <header class="ag-ce-hero">
            <div>
                <p class="ag-ce-kicker">✦ FROM ONE CURIOUS MIND TO ANOTHER</p>
                <h1>Code <em>Exchange.</em></h1>
                <p>Found a neat trick? Solved a tricky problem? Share a small piece of code and the idea behind it.</p>
            </div>
            <div class="ag-ce-deco" aria-hidden="true"><span>&lt;/&gt;</span><i>{ }</i></div>
        </header>

        @if (session('code_success'))
            <div class="ag-ce-success" role="status">✓ {{ session('code_success') }}</div>
        @endif
        @if ($errors->any())
            <div class="ag-ce-error" role="alert">
                <strong>Please review your snippet:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @auth
            <details class="ag-ce-create" @if ($errors->any()) open @endif>
                <summary><span>✦ Share a discovery</span><strong>＋ New snippet</strong></summary>
                <form action="{{ route('code-exchange.store') }}" method="POST" class="ag-ce-form">
                    @csrf
                    <div class="ag-ce-fields">
                        <label>Title
                            <input name="title" type="text" minlength="3" maxlength="90" required value="{{ old('title') }}" placeholder="e.g. A neat way to sum a list">
                        </label>
                        <label>Programming language
                            <select name="language" required>
                                @foreach ($languages as $language)
                                    <option value="{{ $language }}" @selected(old('language', 'Python') === $language)>{{ $language }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label>What does it do? <small>Optional · 700 characters</small>
                        <textarea name="description" maxlength="700" rows="2" placeholder="Explain the idea in a sentence or two…">{{ old('description') }}</textarea>
                    </label>
                    <label>Your code <small>2–8,000 characters</small>
                        <textarea name="code" class="ag-ce-code-input" rows="8" minlength="2" maxlength="8000" required spellcheck="false" placeholder="Paste your code here…">{{ old('code') }}</textarea>
                    </label>
                    <p class="ag-ce-security-note">🔒 Never post passwords, API keys, access tokens or other secrets. Shared snippets are public.</p>
                    <button class="ag-ce-primary" type="submit">Publish snippet ✦</button>
                </form>
            </details>
        @else
            <div class="ag-ce-guest">Want to share a programming tip? <a href="{{ route('login') }}">Log in to post a snippet →</a></div>
        @endauth

        <form class="ag-ce-search" action="{{ route('code-exchange.index') }}" method="GET" role="search">
            @if ($selectedLanguage !== 'all')
                <input type="hidden" name="language" value="{{ $selectedLanguage }}">
            @endif
            @if ($onlyMine)
                <input type="hidden" name="mine" value="1">
            @endif
            <label for="ag-ce-q">Find code, ideas or tips</label>
            <div class="ag-ce-search-row">
                <input id="ag-ce-q" name="q" type="search" value="{{ $search }}" maxlength="100" placeholder="Search titles, descriptions or code…">
                <button type="submit">Search</button>
                <a href="{{ route('code-exchange.index') }}">Clear</a>
            </div>
        </form>

        <div class="ag-ce-filter-row" aria-label="Filter by programming language">
            <a href="{{ route('code-exchange.index', array_filter(['q'=>$search ?: null,'mine'=>$onlyMine ? 1 : null])) }}" @class(['ag-ce-chip', 'is-active' => $selectedLanguage === 'all'])>All</a>
            @foreach ($languages as $language)
                <a href="{{ route('code-exchange.index', array_filter(['language'=>$language,'q'=>$search ?: null,'mine'=>$onlyMine ? 1 : null])) }}"
                    @class(['ag-ce-chip', 'is-active' => $selectedLanguage === $language])>{{ $language }}</a>
            @endforeach
        </div>

        <div class="ag-ce-list-title">
            <div><h2>{{ $onlyMine ? 'My Snippets' : 'Community Snippets' }}</h2><p>{{ $snippets->total() }} snippets</p></div>
            @auth
                <a @class(['ag-ce-mine', 'is-active' => $onlyMine]) href="{{ route('code-exchange.index', array_filter(['q'=>$search ?: null,'language'=>$selectedLanguage === 'all' ? null : $selectedLanguage,'mine'=>$onlyMine ? null : 1])) }}">
                    {{ $onlyMine ? '✓ My Code' : 'View My Code' }}
                </a>
            @endauth
        </div>

        @if ($snippets->isEmpty())
            <div class="ag-ce-empty"><span>&lt;/&gt;</span><h3>No snippets yet.</h3><p>Try a different language or share the first discovery!</p></div>
        @else
            <div class="ag-ce-grid">
                @foreach ($snippets as $snippet)
                    @php $isOwner = auth()->check() && (int) $snippet->user_id === (int) auth()->id(); @endphp
                    <article class="ag-ce-card" id="code-{{ $snippet->id }}">
                        <div class="ag-ce-card-top">
                            <span class="ag-ce-language">⌘ {{ $snippet->language }}</span>
                            <span class="ag-ce-date">{{ $snippet->created_at->format('M j, Y') }}</span>
                        </div>
                        <h3>{{ $snippet->title }}</h3>
                        @if ($snippet->description)
                            <p class="ag-ce-description">{{ $snippet->description }}</p>
                        @endif
                        <div class="ag-ce-code-wrap">
                            <div class="ag-ce-code-header"><span>CODE SNIPPET</span><button type="button" class="ag-ce-copy" data-ag-ce-copy>⧉ Copy Code</button></div>
                            <pre tabindex="0"><code>{{ $snippet->code }}</code></pre>
                        </div>
                        <div class="ag-ce-card-bottom">
                            <span>by {{ $snippet->user?->name ?? 'Community member' }}</span>
                            @if ($isOwner)
                                <div class="ag-ce-owner-actions">
                                    <details class="ag-ce-edit">
                                        <summary>✎ Edit</summary>
                                        <form action="{{ route('code-exchange.update', $snippet) }}" method="POST" class="ag-ce-form ag-ce-edit-form">
                                            @csrf
                                            @method('PATCH')
                                            <label>Title
                                                <input name="title" required minlength="3" maxlength="90" value="{{ $snippet->title }}">
                                            </label>
                                            <label>Language
                                                <select name="language" required>
                                                    @foreach ($languages as $language)
                                                        <option value="{{ $language }}" @selected($snippet->language === $language)>{{ $language }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>Description
                                                <textarea name="description" rows="2" maxlength="700">{{ $snippet->description }}</textarea>
                                            </label>
                                            <label>Code
                                                <textarea name="code" class="ag-ce-code-input" rows="7" required minlength="2" maxlength="8000" spellcheck="false">{{ $snippet->code }}</textarea>
                                            </label>
                                            <button type="submit" class="ag-ce-primary">Save Changes</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('code-exchange.destroy', $snippet) }}" onsubmit="return confirm('Delete this code snippet?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ag-ce-delete" type="submit">⌫ Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <x-social-actions :item="$snippet" kind="code" />
                    </article>
                @endforeach
            </div>
            <div class="ag-ce-pagination">{{ $snippets->links() }}</div>
        @endif
        <footer class="ag-ce-footer"><span>AFTERGLOW ✦ Small discoveries, shared with everyone.</span><a href="{{ route('home') }}">← Back to home</a></footer>
    </div>
</div>
@endsection
