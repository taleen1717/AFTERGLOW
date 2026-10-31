{{-- Shared mood + image picker for Add and Edit. The previews use the 18 photos bundled locally. --}}
<div class="ag-vibe-customizer" data-ag-vibe-picker>
    <label for="ag-vibe-mood-{{ $pickerId }}" class="ag-vibe-label">Choose a mood</label>
    <select id="ag-vibe-mood-{{ $pickerId }}" name="mood" class="input w-full" required data-ag-vibe-mood>
        @foreach ($moodOptions as $value => $label)
            <option value="{{ $value }}" @selected($initialMood === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <fieldset class="ag-vibe-style-fieldset" data-ag-vibe-choices>
        <legend class="ag-vibe-label">Choose your background</legend>
        <p class="ag-vibe-helper">Each mood has 3 designs. Tap one to preview it.</p>
        <div class="ag-vibe-options">
            @for ($styleNumber = 1; $styleNumber <= 3; $styleNumber++)
                <label class="ag-vibe-choice">
                    <input type="radio" class="ag-vibe-choice-radio" name="card_style" value="{{ $styleNumber }}" required
                        @checked((int) $initialStyle === $styleNumber)>
                    <span class="ag-vibe-choice-visual">
                        <img data-ag-vibe-thumb src="{{ $vibeImages[$initialMood][$styleNumber - 1] ?? '' }}"
                            alt="Background {{ $styleNumber }} preview" loading="lazy">
                    </span>
                    <span class="ag-vibe-choice-caption">Design {{ $styleNumber }}</span>
                </label>
            @endfor
        </div>
    </fieldset>


    {{-- Typography is stored with each quote, not as a global theme setting. --}}
    <div class="ag-vibe-type-settings">
        <div>
            <label for="ag-vibe-font-{{ $pickerId }}" class="ag-vibe-label">Quote font</label>
            <select id="ag-vibe-font-{{ $pickerId }}" name="text_font" class="input w-full" required data-ag-vibe-font>
                @foreach ([
                    'modern' => 'Modern · Clean',
                    'rounded' => 'Rounded · Soft',
                    'classic' => 'Classic · Serif',
                    'elegant' => 'Elegant · Editorial',
                    'typewriter' => 'Typewriter · Retro',
                    'arabic' => 'Arabic · Clear',
                ] as $fontCode => $fontLabel)
                    <option value="{{ $fontCode }}" @selected($initialFont === $fontCode)>{{ $fontLabel }}</option>
                @endforeach
            </select>
        </div>
        <fieldset class="ag-vibe-color-fieldset">
            <legend class="ag-vibe-label">Quote text color</legend>
            <p class="ag-vibe-helper">Pick a matching color, or choose any shade with Custom.</p>
            <input type="hidden" name="text_color" value="{{ $initialColor }}" data-ag-selected-color>
            <div class="ag-vibe-color-options" role="group" aria-label="Choose quote text color">
                @foreach ([
                    'auto' => ['Auto', '#65769A'],
                    'navy' => ['Navy', '#182C54'],
                    'plum' => ['Plum', '#632B72'],
                    'rose' => ['Rose', '#9E365F'],
                    'teal' => ['Teal', '#0B666B'],
                    'cobalt' => ['Blue', '#2657B8'],
                    'white' => ['White', '#FFFFFF'],
                    'cream' => ['Cream', '#FFF1D4'],
                ] as $colorCode => $colorItem)
                    <button type="button" class="ag-vibe-color-button" data-ag-color-option="{{ $colorCode }}"
                        aria-pressed="{{ $initialColor === $colorCode ? 'true' : 'false' }}"
                        title="{{ $colorItem[0] }}">
                        <span class="ag-vibe-color-dot" style="--ag-swatch-color: {{ $colorItem[1] }}"></span>
                        <span>{{ $colorItem[0] }}</span>
                    </button>
                @endforeach
                <label class="ag-vibe-custom-color" title="Custom quote color">
                    <span class="ag-vibe-custom-color-dot">＋</span>
                    <span>Custom</span>
                    <input type="color" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string)$initialColor) ? $initialColor : '#A24ABB' }}"
                        data-ag-color-custom aria-label="Choose custom quote color">
                </label>
            </div>
        </fieldset>
    </div>

    <div class="ag-vibe-preview-wrap">
        <div class="ag-vibe-preview-heading">LIVE CARD PREVIEW <span>· Light overlay 20%</span></div>
        <blockquote class="ag-vibe-card ag-vibe-preview {{ isset($vibeImages[$initialMood]) ? '' : 'ag-vibe-card--plain' }}"
            data-ag-vibe-preview data-ag-vibe-mood="{{ $initialMood }}"
            data-ag-text-font="{{ $initialFont }}" data-ag-text-color="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string)$initialColor) ? 'custom' : $initialColor }}"
            @if(isset($vibeImages[$initialMood])) style="--ag-vibe-image: url('{{ $vibeImages[$initialMood][(int)$initialStyle - 1] ?? $vibeImages[$initialMood][0] }}')" @endif>
            <span class="ag-vibe-tag" data-ag-vibe-preview-tag>{{ $moodOptions[$initialMood] ?? 'Just a Quote' }}</span>
            <p class="ag-vibe-quote" data-ag-vibe-preview-text>"Your quote will look like this..."</p>
            <p class="ag-vibe-byline">— you · preview</p>
        </blockquote>
    </div>
</div>
