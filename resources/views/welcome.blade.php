@extends('Layout.app')

@section('title', 'Home')
@section('content')
    <link rel="stylesheet" href="{{ asset('afterglow/quote-vibes.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('afterglow/daily-glow.css') }}?v=1">
    <x-home-services />
    @php
        $editingId = request()->filled('edit') ? (int) request('edit') : null;
        $moodOptions = [
            'Uncategorized' => 'Just a Quote',
            'Dreamy' => 'Dreamy',
            'Night Drive' => 'Night Drive',
            'Cyber' => 'Cyber',
            'Love' => 'Love',
            'Chaos' => 'Chaos',
            'Deep Thoughts' => 'Deep Thoughts',
        ];
        $approvedTextFonts = ['modern', 'rounded', 'classic', 'elegant', 'typewriter', 'arabic'];
        $approvedTextColors = ['auto', 'navy', 'plum', 'rose', 'teal', 'cobalt', 'white', 'cream'];
        $quoteColorPlate = static function (string $hex): string {
            // Choose a contrasting chip behind the text (WCAG relative luminance).
            $channels = [hexdec(substr($hex, 1, 2)) / 255, hexdec(substr($hex, 3, 2)) / 255, hexdec(substr($hex, 5, 2)) / 255];
            $channels = array_map(static fn ($v) => $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $channels);
            $lum = $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;
            return $lum > 0.179 ? 'rgba(8, 15, 37, .94)' : 'rgba(250, 252, 255, .95)';
        };
        $vibeImages = [
            'Dreamy' => [asset('afterglow/quote-vibes/dreamy-1.jpg'), asset('afterglow/quote-vibes/dreamy-2.jpg'), asset('afterglow/quote-vibes/dreamy-3.jpg')],
            'Night Drive' => [asset('afterglow/quote-vibes/night-drive-1.jpg'), asset('afterglow/quote-vibes/night-drive-2.jpg'), asset('afterglow/quote-vibes/night-drive-3.jpg')],
            'Cyber' => [asset('afterglow/quote-vibes/cyber-1.jpg'), asset('afterglow/quote-vibes/cyber-2.jpg'), asset('afterglow/quote-vibes/cyber-3.jpg')],
            'Love' => [asset('afterglow/quote-vibes/love-1.jpg'), asset('afterglow/quote-vibes/love-2.jpg'), asset('afterglow/quote-vibes/love-3.jpg')],
            'Chaos' => [asset('afterglow/quote-vibes/chaos-1.jpg'), asset('afterglow/quote-vibes/chaos-2.jpg'), asset('afterglow/quote-vibes/chaos-3.jpg')],
            'Deep Thoughts' => [asset('afterglow/quote-vibes/deep-thoughts-1.jpg'), asset('afterglow/quote-vibes/deep-thoughts-2.jpg'), asset('afterglow/quote-vibes/deep-thoughts-3.jpg')],
        ];
    @endphp

    <div class="ag-quotes-shell mx-auto w-full px-4 py-8 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- One automatically selected quote for the current Saudi day. --}}
        <x-daily-glow />

        <section class="space-y-4" id="ag-quote-list">
            <div>
                <h2 class="text-2xl font-bold text-white">Quotes</h2>
                <p class="mt-1 text-sm text-white/75">Find the words that match your mood.</p>
            </div>

            {{-- Client-side filters apply to the quotes returned by the current controller. --}}
            <div class="flex flex-wrap gap-2" id="ag-mood-filters" role="group" aria-label="Filter quotes by mood">
                <button type="button" class="ag-mood-filter" data-ag-mood="all" aria-pressed="true">All</button>
                @foreach ($moodOptions as $moodValue => $moodLabel)
                    <button type="button" class="ag-mood-filter" data-ag-mood="{{ $moodValue }}" aria-pressed="false">{{ $moodLabel }}</button>
                @endforeach
            </div>
            <p id="ag-mood-results" class="text-sm text-white/70" role="status" aria-live="polite"></p>
            <p id="ag-mood-no-results" class="hidden rounded-2xl bg-white/90 p-5 text-sm text-tuwaiq-purple" role="status">
                No quotes in this mood yet. Try another mood!
            </p>

            {{-- Responsive card grid: 3 across on desktop, 2 on tablets, 1 on phones. --}}
            <div class="ag-quote-grid" id="ag-quote-grid">
            @forelse ($quotes as $quote)
                @php
                    $isMine = auth()->check() && $quote->user_id === auth()->id();
                    $quoteMood = $quote->mood ?: 'Uncategorized';
                    $quoteMoodLabel = $moodOptions[$quoteMood] ?? 'Just a Quote';
                    $cardStyle = max(1, min(3, (int) ($quote->card_style ?: 1)));
                    $cardImage = $vibeImages[$quoteMood][$cardStyle - 1] ?? null;
                    $textFont = in_array($quote->text_font ?? 'modern', $approvedTextFonts, true) ? $quote->text_font : 'modern';
                    $savedColor = (string) ($quote->text_color ?: 'auto');
                    $isCustomColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $savedColor) === 1;
                    $textColor = $isCustomColor || in_array($savedColor, $approvedTextColors, true) ? $savedColor : 'auto';
                    $colorPlate = $isCustomColor ? $quoteColorPlate($textColor) : null;
                @endphp

                <div class="ag-quote-item {{ $isMine && $quote->id === $editingId ? 'ag-quote-item--editing' : '' }}" data-ag-quote-mood="{{ $quoteMood }}">
                    @if ($isMine && $quote->id === $editingId)
                        <form method="POST" action="{{ route('edit_quote', $quote) }}"
                            class="ag-vibe-edit-form space-y-3 rounded-2xl bg-tuwaiq-purple p-5 shadow" data-ag-vibe-form>
                            @csrf
                            @method('PUT')
                            <label for="ag-edit-text-{{ $quote->id }}" class="ag-vibe-label">Edit your quote</label>
                            <textarea id="ag-edit-text-{{ $quote->id }}" name="content" rows="3" required minlength="3" maxlength="155" class="input w-full" autofocus>{{ old('content', $quote->content) }}</textarea>

                            @include('quotes._vibe-picker', [
                                'pickerId' => 'edit-'.$quote->id,
                                'initialMood' => old('mood', $quoteMood),
                                'initialStyle' => old('card_style', $cardStyle),
                                'initialFont' => old('text_font', $textFont),
                                'initialColor' => old('text_color', $textColor),
                            ])

                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-secondary">Save</button>
                                <a href="{{ route('home') }}" class="btn text-white hover:underline">Cancel</a>
                            </div>
                        </form>
                    @else
                        {{-- Shared visual card, while edit/delete remains available only to its owner. --}}
                        <blockquote class="ag-vibe-card {{ $cardImage ? '' : 'ag-vibe-card--plain' }}" data-ag-vibe-mood="{{ $quoteMood }}"
                            data-ag-text-font="{{ $textFont }}" data-ag-text-color="{{ $isCustomColor ? 'custom' : $textColor }}"
                            style="@if($cardImage) --ag-vibe-image: url('{{ $cardImage }}'); @endif
                                @if($isCustomColor) --ag-user-ink: {{ $textColor }}; --ag-user-plate: {{ $colorPlate }}; @endif">
                            <span class="ag-vibe-tag">✦ {{ $quoteMoodLabel }} @if($cardImage) · Design {{ $cardStyle }} @endif</span>
                            <p class="ag-vibe-quote" title="{{ $quote->content }}">“{{ $quote->content }}”</p>
                            <p class="ag-vibe-byline">
                                — {{ $quote->user->name }} <span>· {{ $quote->created_at->format('Y-m-d') }}</span>
                            </p>
                            @if ($isMine)
                                <div class="ag-vibe-owner-controls">
                                    <a href="{{ route('home', ['edit' => $quote->id]) }}" aria-label="Edit quote" title="Edit">✎</a>
                                    <form method="POST" action="{{ route('delete_quote', $quote) }}"
                                        onsubmit="return confirm('Delete this quote?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Delete quote" title="Delete">⌫</button>
                                    </form>
                                </div>
                            @endif
                        </blockquote>
                    @endif
                </div>
            @empty
                <p class="ag-quote-empty text-gray-300">No quotes yet. Be the first to share one!</p>
            @endforelse
            </div>
        </section>
    </div>

    @auth
        <button type="button" popovertarget="quote-popup" aria-label="Share a quote"
            class="fixed bottom-8 right-8 z-40 flex h-16 w-16 items-center justify-center rounded-full bg-tuwaiq-purple text-white shadow-xl transition hover:scale-110 hover:bg-tuwaiq-purple-light focus:outline-none focus-visible:ring-4 focus-visible:ring-tuwaiq-purple/30">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                stroke="currentColor" class="h-8 w-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>

        <div id="quote-popup" popover
            class="m-auto w-[90%] max-w-lg rounded-3xl bg-white p-0 shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-sm">
            <form method="POST" action="{{ route('home') }}" class="ag-vibe-create-form space-y-4 p-6" data-ag-vibe-form>
                @csrf
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-tuwaiq-purple">Share a quote</h2>
                    <button type="button" popovertarget="quote-popup" popovertargetaction="hide" aria-label="Close"
                        class="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div>
                    <textarea id="quote-body" name="content" rows="4" required minlength="3" maxlength="155" autofocus
                        placeholder="Write your quote…" class="input peer resize-none w-full user-invalid:border-red-500">{{ old('content') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 peer-user-invalid:text-red-600">3–155 characters.</p>
                </div>

                @include('quotes._vibe-picker', [
                    'pickerId' => 'new',
                    'initialMood' => old('mood', 'Uncategorized'),
                    'initialStyle' => old('card_style', 1),
                    'initialFont' => old('text_font', 'modern'),
                    'initialColor' => old('text_color', 'auto'),
                ])

                <button type="submit" class="btn btn-primary w-full">Send</button>
            </form>
        </div>
    @else
        <a href="/login"
            class="fixed bottom-8 right-8 z-40 rounded-full bg-tuwaiq-purple px-6 py-4 text-white shadow-xl hover:bg-tuwaiq-purple-light">
            Log in to share a quote
        </a>
    @endauth

    <script id="ag-vibe-image-data" type="application/json">@json($vibeImages)</script>
    <script src="{{ asset('afterglow/quote-vibes.js') }}?v=3" defer></script>
@endsection
