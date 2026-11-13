@extends('Layout.app')

@section('title', 'Visual Boards — AFTERGLOW')

@section('content')
<link rel="stylesheet" href="{{ asset('afterglow/visual-boards.css') }}?v=1">
<script defer src="{{ asset('afterglow/visual-boards.js') }}?v=1"></script>

<div class="ag-boards-page">
    <div class="ag-boards-wrap">
        <div class="ag-boards-topline">
            <a href="{{ route('home') }}" class="ag-boards-back">← Back to AFTERGLOW</a>
            <span>VISUAL WORLD · ART · MEMORIES · MOODS</span>
        </div>
        <header class="ag-boards-hero">
            <div>
                <p class="ag-boards-kicker">✦ A PLACE FOR YOUR VISUAL STORIES</p>
                <h1>Visual <em>Boards.</em></h1>
                <p class="ag-boards-intro">Share a photo, a drawing, or a little piece of your world. Different moods. One creative space.</p>
            </div>
            <div class="ag-boards-orbit" aria-hidden="true"><span>✧</span><span>✦</span><span>✧</span></div>
        </header>

        @if (session('board_success'))
            <div class="ag-boards-flash" role="status">✓ {{ session('board_success') }}</div>
        @endif
        @if ($errors->any())
            <div class="ag-boards-alert" role="alert">
                <strong>Please review your artwork:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @auth
            <details class="ag-board-create" @if ($errors->any()) open @endif>
                <summary><span>✦ Share your artwork</span><span>＋ Add a visual</span></summary>
                <form action="{{ route('boards.store') }}" method="POST" enctype="multipart/form-data" class="ag-board-form" data-ag-board-form>
                    @csrf
                    <div class="ag-board-fields">
                        <label>Title <span>required</span>
                            <input type="text" name="title" maxlength="90" minlength="2" required value="{{ old('title') }}" placeholder="Give your visual a name…">
                        </label>
                        <label>Mood
                            <select name="mood" required>
                                @foreach ($moods as $mood)
                                    <option value="{{ $mood }}" @selected(old('mood', 'Dreamy') === $mood)>{{ $mood }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label>Description <span>optional · max 500 characters</span>
                        <textarea name="description" maxlength="500" rows="3" placeholder="What inspired this piece?">{{ old('description') }}</textarea>
                    </label>
                    <label>Image <span>JPG, PNG, WEBP, GIF · up to 5 MB</span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required data-ag-board-file>
                    </label>
                    <div class="ag-board-image-preview" hidden><img alt="New artwork preview" data-ag-board-preview></div>
                    <button class="ag-board-primary" type="submit">Share on Visual Boards ✦</button>
                </form>
            </details>
        @else
            <div class="ag-boards-guest">Want to share your art? <a href="{{ route('login') }}">Log in to post a visual →</a></div>
        @endauth

        <div class="ag-board-toolbar">
            <div class="ag-board-filter-list" aria-label="Filter visuals by mood">
                <a href="{{ route('boards.index', $onlyMine ? ['mine'=>1] : []) }}" @class(['ag-board-filter', 'is-active' => $selectedMood === 'all'])>All</a>
                @foreach ($moods as $mood)
                    <a href="{{ route('boards.index', array_filter(['mood'=>$mood,'mine'=>$onlyMine ? 1 : null])) }}"
                        @class(['ag-board-filter', 'is-active' => $selectedMood === $mood])>{{ $mood }}</a>
                @endforeach
            </div>
            @auth
                <a @class(['ag-boards-mine','is-active' => $onlyMine]) href="{{ route('boards.index', array_filter(['mood'=>$selectedMood !== 'all' ? $selectedMood : null, 'mine'=> $onlyMine ? null : 1])) }}">
                    {{ $onlyMine ? '✓ My Artwork' : 'View My Artwork' }}
                </a>
            @endauth
        </div>

        <div class="ag-boards-heading"><h2>{{ $onlyMine ? 'My Visuals' : 'Explore the Gallery' }}</h2><span>{{ $boards->total() }} pieces</span></div>
        @if ($boards->isEmpty())
            <div class="ag-boards-empty"><span>✧</span><h3>No artwork here yet.</h3><p>Pick another mood, or share the first visual!</p></div>
        @else
            <div class="ag-board-grid">
                @foreach ($boards as $board)
                    @php $isOwner = auth()->check() && (int) $board->user_id === (int) auth()->id(); @endphp
                    <article class="ag-board-card">
                        <a class="ag-board-art" href="{{ route('boards.image', $board) }}" target="_blank" rel="noopener" aria-label="View full image: {{ $board->title }}">
                            <img src="{{ route('boards.image', $board) }}" alt="{{ $board->title }}" loading="lazy">
                            <span class="ag-board-full">↗ View full art</span>
                        </a>
                        <div class="ag-board-info">
                            <span class="ag-board-mood">✧ {{ $board->mood }}</span>
                            <h3>{{ $board->title }}</h3>
                            @if ($board->description)<p class="ag-board-desc">{{ $board->description }}</p>@endif
                            <div class="ag-board-meta"><span>@if($board->user?->avatar_path)<img class="ag-v2-author-avatar" src="{{ route('users.avatar', $board->user) }}" alt="">@endif by {{ $board->user?->name ?? 'Community member' }}</span><time datetime="{{ $board->created_at->toDateString() }}">{{ $board->created_at->format('M j, Y') }}</time></div>
                            @if ($isOwner)
                                <div class="ag-board-owner">
                                    <details class="ag-board-edit">
                                        <summary>✎ Edit</summary>
                                        <form method="POST" action="{{ route('boards.update', $board) }}" enctype="multipart/form-data" class="ag-board-form ag-board-edit-form" data-ag-board-form>
                                            @csrf
                                            @method('PATCH')
                                            <label>Title <input type="text" name="title" minlength="2" maxlength="90" required value="{{ $board->title }}"></label>
                                            <label>Mood
                                                <select name="mood" required>
                                                    @foreach ($moods as $mood)
                                                        <option value="{{ $mood }}" @selected($board->mood === $mood)>{{ $mood }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>Description<textarea name="description" maxlength="500" rows="3">{{ $board->description }}</textarea></label>
                                            <label>Replace image (optional)
                                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" data-ag-board-file>
                                            </label>
                                            <div class="ag-board-image-preview" hidden><img alt="Replacement image preview" data-ag-board-preview></div>
                                            <button class="ag-board-primary" type="submit">Save Changes</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('boards.destroy', $board) }}" onsubmit="return confirm('Delete this artwork?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ag-board-delete" type="submit">⌫ Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <x-social-actions :item="$board" kind="board" />
                    </article>
                @endforeach
            </div>
            <div class="ag-board-pages">{{ $boards->links() }}</div>
        @endif
        <footer class="ag-boards-footer"><span>AFTERGLOW ✦ Two worlds, countless stories.</span><a href="{{ route('home') }}">Back to quotes ↑</a></footer>
    </div>
</div>
@endsection
