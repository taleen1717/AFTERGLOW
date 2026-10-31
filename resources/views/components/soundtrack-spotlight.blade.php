@php
    $listener = auth()->user();
    $onRepeat = $listener?->favoriteSongs()->where('is_on_repeat', true)->first();
    $songTotal = $listener ? $listener->favoriteSongs()->count() : null;
@endphp

<section class="ag-spotlight" aria-labelledby="ag-soundtrack-heading">
    <div class="ag-spotlight-copy">
        <p class="ag-spotlight-eyebrow">FEATURED SPACE <span>✦</span> THE MUSIC ARCHIVE</p>
        <h2 id="ag-soundtrack-heading">MY <em>SOUNDTRACK</em><span class="ag-spotlight-star">✧</span></h2>
        <p class="ag-spotlight-tagline">Every mood has a song.</p>
        <p class="ag-spotlight-description">Collect your favorites, find your current obsession, and give your quotes a soundtrack of their own.</p>
        <div class="ag-spotlight-actions">
            @auth
                <a class="ag-spotlight-primary" href="{{ route('soundtrack.index') }}">Open My Soundtrack <span aria-hidden="true">↗</span></a>
                <span class="ag-spotlight-count">{{ $songTotal }} {{ $songTotal === 1 ? 'track' : 'tracks' }} in your collection</span>
            @else
                <a class="ag-spotlight-primary" href="{{ url('/login') }}">Log in to create yours <span aria-hidden="true">↗</span></a>
                <span class="ag-spotlight-count">Personal music libraries · Moods · Favorite tracks</span>
            @endauth
        </div>
        @auth
            <div class="ag-spotlight-now">
                <span class="ag-spotlight-live"><span aria-hidden="true">●</span> CURRENTLY ON REPEAT</span>
                @if ($onRepeat)
                    <span class="ag-spotlight-track">{{ $onRepeat->title }} <span>— {{ $onRepeat->artist }}</span></span>
                @else
                    <span class="ag-spotlight-track">Choose a track to make it yours.</span>
                @endif
            </div>
        @endauth
    </div>
    <div class="ag-spotlight-art" aria-hidden="true">
        <div class="ag-spotlight-orbit ag-spotlight-orbit-one"></div>
        <div class="ag-spotlight-orbit ag-spotlight-orbit-two"></div>
        <span class="ag-spotlight-spark ag-spotlight-spark-one">✦</span>
        <span class="ag-spotlight-spark ag-spotlight-spark-two">✧</span>
        <div class="ag-spotlight-disc"><div class="ag-spotlight-disc-label">AFTERGLOW<span>33⅓ RPM</span></div></div>
        <div class="ag-spotlight-mood">NIGHT DRIVE <span>✦</span> ICY DREAM <span>✦</span> NEON RUSH</div>
    </div>
</section>
