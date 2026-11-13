{{-- AFTERGLOW Home Creative Hub: presentation only. Existing service pages and data remain unchanged. --}}
<link rel="stylesheet" href="{{ asset('afterglow/home-creative-hub.css') }}?v=7">

<section class="ag-hub" aria-labelledby="ag-hub-heading">
    <header class="ag-hub-heading">
        <div>
            <p class="ag-hub-eyebrow"><span aria-hidden="true">✦</span> YOUR CREATIVE UNIVERSE</p>
            <h2 id="ag-hub-heading">Everything you love, <em>in one place.</em></h2>
            <p class="ag-hub-intro">Words, sounds, art and code — explore your own corner of AFTERGLOW.</p>
            <a href="{{ route('soundtrack.discover') }}" class="ag-v2-home-music">♫ Explore the Community Soundtrack ↗</a>
        </div>
        <span class="ag-hub-heading-mark" aria-hidden="true">✳&nbsp; 5 SPACES &nbsp;✳</span>
    </header>

    <div class="ag-hub-layout">
        <div class="ag-hub-soundtrack">
            <x-soundtrack-spotlight />
        </div>

        <nav class="ag-hub-links" aria-label="Explore AFTERGLOW features">
            <a class="ag-hub-tile ag-hub-quote" href="{{ route('home') }}#ag-quote-list">
                <span class="ag-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11H5V7a3 3 0 0 1 3-3h1"/><path d="M9 11v3a4 4 0 0 1-4 4H4"/><path d="M21 11h-5V7a3 3 0 0 1 3-3h1"/><path d="M20 11v3a4 4 0 0 1-4 4h-1"/></svg>
                </span>
                <span class="ag-hub-tile-body"><span class="ag-hub-tile-tag">01 / WRITE & DESIGN</span><strong>Quote Studio</strong><span>Give your thoughts a mood, font and style.</span></span>
                <span class="ag-hub-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="ag-hub-tile ag-hub-art" href="{{ route('boards.index') }}">
                <span class="ag-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.7" cy="8.5" r="1.5"/><path d="m3 17 5-5 4 3 4-5 5 6"/></svg>
                </span>
                <span class="ag-hub-tile-body"><span class="ag-hub-tile-tag">02 / SEE & SHARE</span><strong>Visual Boards</strong><span>Your photos, sketches and inspiration.</span></span>
                <span class="ag-hub-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="ag-hub-tile ag-hub-code" href="{{ route('code-exchange.index') }}">
                <span class="ag-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m8 8-4 4 4 4M16 8l4 4-4 4m-3-12-2 20"/></svg>
                </span>
                <span class="ag-hub-tile-body"><span class="ag-hub-tile-tag">03 / BUILD & LEARN</span><strong>Code Exchange</strong><span>Share snippets and discover ideas.</span></span>
                <span class="ag-hub-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="ag-hub-tile ag-hub-glow" href="{{ route('home') }}#ag-dg-title">
                <span class="ag-hub-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5L12 2Z"/><path d="M19 2v3M17.5 3.5h3"/></svg>
                </span>
                <span class="ag-hub-tile-body"><span class="ag-hub-tile-tag">04 / FEEL INSPIRED</span><strong>Daily Glow</strong><span>A fresh community quote every day.</span></span>
                <span class="ag-hub-arrow" aria-hidden="true">↗</span>
            </a>
        </nav>
    </div>
</section>
