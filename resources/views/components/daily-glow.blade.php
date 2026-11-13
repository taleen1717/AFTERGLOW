@php
    $mood = $dailyQuote?->mood ?: 'Uncategorized';
    $validMoods = [
        'Dreamy' => 'dreamy',
        'Night Drive' => 'night-drive',
        'Cyber' => 'cyber',
        'Love' => 'love',
        'Chaos' => 'chaos',
        'Deep Thoughts' => 'deep-thoughts',
    ];
    $slug = $validMoods[$mood] ?? null;
    $design = max(1, min(3, (int) ($dailyQuote?->card_style ?: 1)));
    $background = $slug ? asset("afterglow/quote-vibes/{$slug}-{$design}.jpg") : null;

    $font = (string) ($dailyQuote?->text_font ?: 'modern');
    $allowedFonts = ['modern', 'rounded', 'classic', 'elegant', 'typewriter', 'arabic'];
    $font = in_array($font, $allowedFonts, true) ? $font : 'modern';

    $color = (string) ($dailyQuote?->text_color ?: 'auto');
    $presetColors = ['auto', 'navy', 'plum', 'rose', 'teal', 'cobalt', 'white', 'cream'];
    $isCustomColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
    $color = $isCustomColor || in_array($color, $presetColors, true) ? $color : 'auto';

    $customPlate = null;
    if ($isCustomColor) {
        $channel = static function (int $v): float {
            $s = $v / 255;
            return $s <= 0.04045 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        };
        $luminance = .2126 * $channel(hexdec(substr($color, 1, 2)))
                   + .7152 * $channel(hexdec(substr($color, 3, 2)))
                   + .0722 * $channel(hexdec(substr($color, 5, 2)));
        $customPlate = $luminance > 0.179 ? 'rgba(8,15,37,.94)' : 'rgba(250,252,255,.95)';
    }
@endphp

<section class="ag-dg" aria-labelledby="ag-dg-title" data-ag-dg-mood="{{ $mood }}"
    data-ag-dg-font="{{ $font }}" data-ag-dg-color="{{ $isCustomColor ? 'custom' : $color }}"
    style="@if($background) --ag-dg-image: url('{{ $background }}'); @endif
           @if($isCustomColor) --ag-dg-user-ink: {{ $color }}; --ag-dg-user-plate: {{ $customPlate }}; @endif">
    <div class="ag-dg-inner">
        <div class="ag-dg-top">
            <span class="ag-dg-eyebrow"><span class="ag-dg-sparkle" aria-hidden="true">✦</span> DAILY GLOW</span>
            <span class="ag-dg-date">{{ $todayLabel }}</span>
        </div>

        <div class="ag-dg-layout">
            <div class="ag-dg-left">
                <h2 id="ag-dg-title">A little glow for today.</h2>
                <p class="ag-dg-subtitle">One community quote, featured each day.</p>
                <a class="ag-dg-explore" href="#ag-quote-list">Explore Quotes <span aria-hidden="true">↗</span></a>
            </div>
            <div class="ag-dg-right">
                @if ($dailyQuote)
                    <span class="ag-dg-mood">{{ $mood === 'Uncategorized' ? 'Just a Quote' : $mood }} <span aria-hidden="true">✧</span></span>
                    <blockquote class="ag-dg-blockquote">
                        <p class="ag-dg-text">“{{ $dailyQuote->content }}”</p>
                        <footer class="ag-dg-author">— {{ $dailyQuote->user?->name ?? 'AFTERGLOW Community' }}</footer>
                    </blockquote>
                    <a class="ag-dg-react-link" href="{{ route('home') }}#quote-{{ $dailyQuote->id }}">♡ Like or comment on this quote ↗</a>
                @else
                    <p class="ag-dg-empty">Today's glow starts with the first quote. Share one to light up this space ✨</p>
                @endif
            </div>
        </div>
    </div>
</section>
