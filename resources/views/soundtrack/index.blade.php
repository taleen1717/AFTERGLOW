@extends('Layout.app')
@section('title', 'My Soundtrack — AFTERGLOW')
@section('content')
<link rel="stylesheet" href="{{ asset('afterglow/soundtrack.css') . '?v=final-1030' }}">
<link rel="stylesheet" href="{{ asset('afterglow/music-dual.css') . '?v=final-1030' }}">
<link rel="stylesheet" href="{{ asset('afterglow/vinyl-view.css') . '?v=final-1030' }}">
<link rel="stylesheet" href="{{ asset('afterglow/playback-fallback.css') . '?v=final-1030' }}">
<link rel="stylesheet" href="{{ asset('afterglow/music-precheck.css') . '?v=final-1030' }}">
<div class="ag-soundtrack-shell" id="ag-soundtrack">
    <header class="ag-music-hero">
        <div>
            <p class="ag-music-kicker">AFTERGLOW / YOUR PERSONAL MUSIC UNIVERSE</p>
            <h1>MY <em>SOUNDTRACK</em><span aria-hidden="true"> ✦</span></h1>
            <p>Find the sound of your world.</p>
            <div class="ag-music-links"><a href="{{ route('home') }}">← Back to Quotes</a><a href="#song-add">+ Discover Music</a><a href="{{ route('soundtrack.discover') }}">✦ Community Soundtrack</a></div>
        </div>
        <div class="ag-hero-vinyl" aria-hidden="true"><div class="ag-vinyl-ring"><span>AFTERGLOW</span></div></div>
    </header>

    @if (session('soundtrack_success'))<div class="ag-music-alert ag-success" role="status">✦ {{ session('soundtrack_success') }}</div>@endif
    @if ($errors->any())
        <div class="ag-music-alert ag-error" role="alert"><strong>Please check:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="ag-now-playing">
        <div class="ag-section-heading"><p>01 / ON MY MIND</p><h2>Currently on Repeat <span>↻</span></h2></div>
        @php $repeatSong = $songs->firstWhere('is_on_repeat', true); @endphp
        @if ($repeatSong)
            <div class="ag-repeat-content">
                <div class="ag-mini-disc ag-disc-cover" aria-label="Current song vinyl record">
                    @if ($repeatSong->artwork_url)<img src="{{ $repeatSong->artwork_url }}" alt="Cover of {{ $repeatSong->title }}" loading="lazy">@endif
                </div>
                <div><p class="ag-now-label">NOW SPINNING · {{ $repeatSong->resolved_platform === 'spotify' ? 'SPOTIFY' : 'YOUTUBE' }} · {{ $repeatSong->mood }}</p>
                    <h3>{{ $repeatSong->title }}</h3><p>{{ $repeatSong->artist }}</p>
                    <a href="#song-{{ $repeatSong->id }}">Play in your library ↓</a>
                </div>
            </div>
        @else
            <p class="ag-empty-repeat">No track on repeat yet. Choose a song from your collection.</p>
        @endif
    </section>

    <section class="ag-music-form-section" id="song-add">
        <div class="ag-section-heading"><p>02 / MUSIC DISCOVERY</p><h2>Find Your Next Obsession <span>✧</span></h2></div>
        <p class="ag-music-subintro">Two music worlds. One personal soundtrack.</p>
        <div class="ag-source-switch" role="tablist" aria-label="Music provider">
            <button type="button" class="ag-source-tab is-active" data-tab="youtube" aria-selected="true" role="tab">▶ YouTube · Live Search</button>
            <button type="button" class="ag-source-tab" data-tab="spotify" aria-selected="false" role="tab">◉ Spotify · Embed</button>
        </div>

        <div class="ag-source-panel" id="ag-youtube-panel" role="tabpanel" data-youtube-url="{{ route('soundtrack.youtube-search') }}">
            <label class="ag-search-label" for="ag-youtube-query">Search by song and artist</label>
            <div class="ag-search-bar">
                <input type="search" id="ag-youtube-query" maxlength="140" placeholder="e.g. Red Taylor Swift official audio" autocomplete="off">
                <button type="button" class="ag-button-primary" id="ag-youtube-find">Search YouTube</button>
            </div>
            <p class="ag-help" id="ag-youtube-status" role="status">Choose a search result and play its preview before saving. Blocked videos are filtered where possible.</p>
            <div class="ag-discovery-results" id="ag-youtube-results" aria-live="polite"></div>
            <div class="ag-selection" id="ag-youtube-picked" hidden></div>
            <form method="POST" action="{{ route('soundtrack.store') }}" class="ag-add-song-form ag-discovery-save" id="ag-youtube-save">
                @csrf
                <input type="hidden" name="platform" value="youtube">
                <input type="hidden" name="video_id" id="ag-video-id">
                <label>Mood<select name="mood">@foreach($moods as $mood)<option value="{{ $mood }}">{{ $mood }}</option>@endforeach</select></label>
                <label>Attach to your quote (optional)<select name="quote_id"><option value="">No quote attached</option>@foreach($quotes as $quote)<option value="{{ $quote->id }}">{{ \Illuminate\Support\Str::limit($quote->content, 48) }}</option>@endforeach</select></label>
                <label class="ag-span-all">Who can see this song?
                    <select name="is_public" required><option value="0" selected>Private — only in my library</option><option value="1">Public — share with everyone</option></select>
                </label>
                <button type="submit" class="ag-button-primary ag-span-all" id="ag-youtube-save-btn" disabled>Choose a result, then verify playback</button>
            </form>
        </div>

        <div class="ag-source-panel" id="ag-spotify-panel" role="tabpanel" hidden>
            <p class="ag-help">Spotify can play here without developer keys. To identify the exact Spotify recording, search Spotify and paste its Share link once.</p>
            <div class="ag-search-bar">
                <input id="ag-spotify-search-text" type="search" placeholder="Search artist or song on Spotify..." maxlength="140">
                <a class="ag-music-search-spotify" id="ag-spotify-search-link" href="https://open.spotify.com/search" target="_blank" rel="noopener noreferrer">Search Spotify ↗</a>
            </div>
            <form method="POST" action="{{ route('soundtrack.store') }}" class="ag-add-song-form ag-spotify-form" data-spotify-metadata-url="{{ route('soundtrack.spotify-metadata') }}">
                @csrf
                <input type="hidden" name="platform" value="spotify">
                <label class="ag-span-all">Spotify Share URL
                    <input type="url" name="spotify_url" required maxlength="500" placeholder="https://open.spotify.com/track/..." autocomplete="off">
                </label>
                <p class="ag-help ag-span-all" id="ag-spotify-meta-status" role="status" aria-live="polite">Paste a Spotify link to fill in its title automatically. Artist details are filled when Spotify catalog access is available; otherwise you can type them.</p>
                <label>Song / album / playlist name<input name="title" maxlength="120" placeholder="Auto-filled from Spotify; editable"></label>
                <label>Artist / curator<input name="artist" maxlength="120" placeholder="Auto-filled when available; editable"></label>
                <label>Mood<select name="mood">@foreach($moods as $mood)<option value="{{ $mood }}">{{ $mood }}</option>@endforeach</select></label>
                <label>Attach to your quote (optional)<select name="quote_id"><option value="">No quote attached</option>@foreach($quotes as $quote)<option value="{{ $quote->id }}">{{ \Illuminate\Support\Str::limit($quote->content, 48) }}</option>@endforeach</select></label>
                <label class="ag-span-all">Who can see this song?
                    <select name="is_public" required><option value="0" selected>Private — only in my library</option><option value="1">Public — share with everyone</option></select>
                </label>
                <button type="submit" class="ag-button-primary ag-span-all">+ Add Spotify to My Soundtrack</button>
            </form>
            <p class="ag-help">Supports Spotify tracks, albums, playlists, artists and podcasts via official Spotify embeds. Playback may be limited by Spotify availability and account status.</p>
        </div>
    </section>

    <section class="ag-music-library" id="song-library">
        <div class="ag-section-heading"><p>03 / PERSONAL COLLECTION</p><h2>My Music Library <span>({{ $songs->count() }})</span></h2></div>
        <div class="ag-soundtrack-filters">
            <label>Search library <input id="soundtrack-search" type="search" placeholder="Search song or artist..." autocomplete="off"></label>
            <label>Filter mood <select id="soundtrack-mood"><option value="">All moods</option>@foreach($moods as $mood)<option value="{{ $mood }}">{{ $mood }}</option>@endforeach</select></label>
        </div>
        @if ($songs->isEmpty())
            <div class="ag-empty-library">Your soundtrack is waiting for its first song. ♫</div>
        @else
        <div class="ag-song-grid" id="soundtrack-list">
            @foreach($songs as $song)
            <article class="ag-song-card" id="song-{{ $song->id }}" data-mood="{{ $song->mood }}" data-query="{{ mb_strtolower($song->title . ' ' . $song->artist) }}" data-song-title="{{ $song->title }}" data-song-artist="{{ $song->artist }}">
                <div class="ag-song-art"><div class="ag-vinyl-disc" aria-label="Vinyl disc with the music cover"><div class="ag-vinyl-cover">
                    @if ($song->artwork_url)<img src="{{ $song->artwork_url }}" alt="Artwork for {{ $song->title }}" loading="lazy">@else<span aria-hidden="true">♫</span>@endif
                </div></div><small>{{ $song->resolved_platform === 'spotify' ? '◉ Spotify' : '▶ YouTube' }}</small></div>
                <div class="ag-song-body">
                    <div class="ag-song-tags"><span class="ag-song-mood">{{ $song->mood }}</span>@if($song->is_on_repeat)<span class="ag-repeat-tag">↻ ON REPEAT</span>@endif</div>
                    <h3>{{ $song->title }}</h3><p class="ag-song-artist">{{ $song->artist }} · <strong>{{ $song->is_public ? '🌍 Public' : '🔒 Private' }}</strong></p>
                    @if ($song->quote)<blockquote class="ag-song-linked-quote">“{{ \Illuminate\Support\Str::limit($song->quote->content, 125) }}”</blockquote>@endif
                    <div class="ag-song-actions">
                        <a href="{{ $song->url }}" target="_blank" rel="noopener noreferrer" class="ag-listen-link">Open in {{ ucfirst($song->resolved_platform) }} ↗</a>
                        <form method="POST" action="{{ route('soundtrack.repeat', $song) }}">@csrf @method('PATCH')<button type="submit" class="ag-mini-button">{{ $song->is_on_repeat ? 'Unpin Repeat' : 'Set on Repeat' }}</button></form>
                    </div>
                    @if ($song->embed_url)
                    <details class="ag-player-details ag-vp-details @if($song->resolved_platform === 'youtube') ag-vp-youtube @endif">
                        <summary>▶ Open Music Player</summary>
                        <div class="ag-vp-switcher" role="group" aria-label="Choose player view for {{ $song->title }}">
                            <button type="button" class="ag-vp-tab is-active" data-vp-mode="vinyl" aria-pressed="true">◉ Vinyl View</button>
                            <button type="button" class="ag-vp-tab" data-vp-mode="video" aria-pressed="false">{{ $song->resolved_platform === 'spotify' ? '▣ Spotify Player' : '▣ Video View' }}</button>
                        </div>
                        <div class="ag-vp-stage is-vinyl" data-vp-stage>
                            <div class="ag-vp-artwork" aria-label="Animated vinyl record with artwork for {{ $song->title }}">
                                <div class="ag-vp-record">
                                    <div class="ag-vp-label">
                                        @if ($song->artwork_url)
                                            <img src="{{ $song->artwork_url }}" alt="" loading="lazy">
                                        @else
                                            <span aria-hidden="true">♫</span>
                                        @endif
                                    </div>
                                    <span class="ag-vp-hole" aria-hidden="true"></span>
                                </div>
                                <div class="ag-vp-caption"><strong>{{ $song->title }}</strong><span>{{ $song->artist }}</span></div>
                            </div>
                            <div class="ag-vp-embed-box">
                                <p class="ag-vp-embed-label">{{ $song->resolved_platform === 'spotify' ? 'SPOTIFY · OFFICIAL EMBED' : 'YOUTUBE · OFFICIAL VIDEO PLAYER' }}</p>
                                <iframe loading="lazy" @if($song->resolved_platform === 'youtube') data-ag-youtube-player="1" @endif src="{{ $song->embed_url }}" title="{{ ucfirst($song->resolved_platform) }} player for {{ $song->title }}" allow="autoplay; encrypted-media; clipboard-write; fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                @if ($song->resolved_platform === 'youtube')
                                    <p class="ag-vp-hint">Vinyl View keeps the official YouTube player visible. The spinning record is decorative, not a separate audio stream.</p>
                                    <div class="ag-playback-tools">
                                        <button type="button" class="ag-player-help-toggle" aria-expanded="false">Video not playing?</button>
                                    </div>
                                    <div class="ag-player-fallback" hidden role="status" aria-live="polite">
                                        <p class="ag-fallback-title">This version may be unavailable inside AFTERGLOW.</p>
                                        <p class="ag-fallback-info">Choose another recording, or find the song on Spotify. Some YouTube uploads cannot be embedded.</p>
                                        <div class="ag-fallback-actions">
                                            <button type="button" class="ag-try-alternative">↻ Try Another Version</button>
                                            <a class="ag-spotify-fallback" href="https://open.spotify.com/search/{{ rawurlencode($song->title . ' ' . $song->artist) }}" target="_blank" rel="noopener noreferrer">◉ Find on Spotify ↗</a>
                                            <a href="{{ $song->url }}" target="_blank" rel="noopener noreferrer">▶ Open on YouTube ↗</a>
                                        </div>
                                        <p class="ag-fallback-note">If you choose a different recording, save it and remove the unavailable one whenever you want.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </details>
                    @endif
                    @if ($song->is_public)
                        <x-social-actions :item="$song" kind="song" />
                    @else
                        <p class="ag-song-privacy-note">🔒 Private song — only you can see it. Share it publicly to receive likes and comments.</p>
                    @endif
                    <details class="ag-song-edit"><summary>✎ Edit details</summary><form method="POST" action="{{ route('soundtrack.update', $song) }}">
                        @csrf @method('PUT')
                        <label>Title<input name="title" required maxlength="120" value="{{ $song->title }}"></label>
                        <label>Artist<input name="artist" required maxlength="120" value="{{ $song->artist }}"></label>
                        <label>Mood<select name="mood">@foreach($moods as $mood)<option value="{{ $mood }}" @selected($song->mood === $mood)>{{ $mood }}</option>@endforeach</select></label>
                        <label>Quote<select name="quote_id"><option value="">None</option>@foreach($quotes as $quote)<option value="{{ $quote->id }}" @selected($song->quote_id === $quote->id)>{{ \Illuminate\Support\Str::limit($quote->content, 36) }}</option>@endforeach</select></label>
                        <label>Visibility<select name="is_public" required><option value="0" @selected(! $song->is_public)>Private — only me</option><option value="1" @selected($song->is_public)>Public — community</option></select></label>
                        <button type="submit" class="ag-button-primary">Save Changes</button>
                    </form></details>
                    <form method="POST" action="{{ route('soundtrack.destroy', $song) }}" class="ag-delete-song" onsubmit="return confirm('Remove this song from your soundtrack?')">@csrf @method('DELETE')<button type="submit">Remove from library ×</button></form>
                </div>
            </article>
            @endforeach
        </div>
        <p class="ag-no-match" id="soundtrack-no-match" hidden>No songs match these filters.</p>
        @endif
    </section>
</div>
<script defer src="{{ asset('afterglow/soundtrack.js') . '?v=final-1030' }}"></script>
<script defer src="{{ asset('afterglow/music-dual.js') . '?v=final-1030' }}"></script>
<script defer src="{{ asset('afterglow/vinyl-view.js') . '?v=final-1030' }}"></script>
<script defer src="{{ asset('afterglow/playback-fallback.js') . '?v=final-1030' }}"></script>

<script defer src="{{ asset('afterglow/spotify-autofill.js') }}?v=20261113-2"></script>
@endsection
