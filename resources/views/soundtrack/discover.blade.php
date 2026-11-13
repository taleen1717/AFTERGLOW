@extends('Layout.app')
@section('title', 'Community Soundtrack — AFTERGLOW')
@section('content')
<link rel="stylesheet" href="{{ asset('afterglow/community-player.css') }}?v=1">
<section class="ag-v2-shell">
    <header class="ag-v2-hero">
        <p class="ag-v2-kicker">AFTERGLOW / COMMUNITY MUSIC</p>
        <h1>Community <em>Soundtrack</em> ♫</h1>
        <p>Discover songs people chose to share, leave a heart or start a conversation. Private songs never appear here.</p>
        <a class="ag-v2-back" href="{{ auth()->check() ? route('soundtrack.index') : route('home') }}">← Back to {{ auth()->check() ? 'My Soundtrack' : 'AFTERGLOW' }}</a>
    </header>
    <div class="ag-v2-song-grid">
        @forelse($songs as $song)
            <article class="ag-v2-song-card" id="public-song-{{ $song->id }}">
                <div class="ag-v2-song-main">
                    <div class="ag-v2-song-cover">
                        @if($song->artwork_url)
                            <img src="{{ $song->artwork_url }}" alt="" loading="lazy">
                        @else
                            ♫
                        @endif
                    </div>
                    <div class="ag-v2-song-info">
                        <span class="ag-v2-kicker">{{ $song->mood }} · {{ ucfirst($song->resolved_platform) }}</span>
                        <h2>{{ $song->title }}</h2>
                        <p>{{ $song->artist }}</p>
                        <small>Shared by {{ $song->user?->name ?? 'Community member' }}</small>
                        <span class="ag-community-service">{{ $song->resolved_platform === 'spotify' ? 'Spotify' : 'YouTube' }} · Official player</span>
                    </div>
                </div>
                <div class="ag-community-actions">
                    @if($song->embed_url)
                        <details class="ag-community-player" data-community-song>
                            <summary class="ag-community-play-toggle">
                                <span class="ag-community-play-icon" aria-hidden="true">▶</span>
                                <span>Play here</span>
                                <span class="ag-community-play-chevron" aria-hidden="true">⌄</span>
                            </summary>
                            <div class="ag-community-player-content">
                                <div class="ag-community-embed {{ $song->resolved_platform === 'spotify' ? 'is-spotify' : 'is-youtube' }}">
                                    <iframe
                                        title="{{ ucfirst($song->resolved_platform) }} player — {{ $song->title }}"
                                        data-community-src="{{ $song->embed_url . ($song->resolved_platform === 'youtube' ? '?playsinline=1&rel=0' : '') }}"
                                        loading="lazy"
                                        allow="autoplay; encrypted-media; clipboard-write; picture-in-picture; fullscreen"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                                <p class="ag-community-player-note">
                                    Playback uses the official {{ ucfirst($song->resolved_platform) }} player. Some recordings cannot play on other websites.
                                    <a href="{{ $song->url }}" target="_blank" rel="noopener noreferrer">Open on {{ ucfirst($song->resolved_platform) }} ↗</a>
                                </p>
                            </div>
                        </details>
                    @else
                        <p class="ag-community-player-note ag-community-unavailable">An embedded player is unavailable for this link. <a href="{{ $song->url }}" target="_blank" rel="noopener noreferrer">Open on {{ ucfirst($song->resolved_platform) }} ↗</a></p>
                    @endif
                </div>
                <x-social-actions :item="$song" kind="song" />
            </article>
        @empty
            <p class="ag-v2-empty">No public songs yet. Share the first one from My Soundtrack ✦</p>
        @endforelse
    </div>
    <div class="ag-v2-pagination">{{ $songs->links() }}</div>
</section>
<script defer src="{{ asset('afterglow/community-player.js') }}?v=1"></script>
@endsection
