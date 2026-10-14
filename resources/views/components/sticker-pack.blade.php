@php
    // Pinking-shear teeth along the two crimped ends of the packet.
    $teeth = fn (int $from, int $step, int $tip, int $base) => collect(range(1, 20))
        ->map(fn (int $i) => 'L'.($from + $step * ($i * 10 - 5)).' '.$tip.' L'.($from + $step * $i * 10).' '.$base)
        ->implode(' ');
    $flap = 'M12 16 '.$teeth(12, 1, 10, 16).' L212 50 L12 50 Z';
    // The torn top edge stays hidden under the flap until the pack is ripped open.
    $body = 'M12 46 L26 41 L38 47 L52 42 L61 46 L77 40 L90 45 L104 41 L118 47 L131 42 L146 46 L158 40 L172 45 L186 41 L199 46 L212 42 L212 300 '.$teeth(212, -1, 306, 300).' Z';
    $burst = collect(range(0, 27))
        ->map(fn (int $i) => round(166 + ($i % 2 ? 31 : 38) * cos(M_PI * $i / 14), 1).','.round(232 + ($i % 2 ? 31 : 38) * sin(M_PI * $i / 14), 1))
        ->implode(' ');
    $day = mb_strtoupper(today(config('app.timezone'))->locale('it')->isoFormat('ddd D MMM'));
@endphp

<svg {{ $attributes->class('pack') }} viewBox="0 0 240 330" aria-hidden="true" focusable="false">
    <path class="pack__shadow pack__shadow--flap" d="{{ $flap }}" transform="translate(8 8)" />
    <path class="pack__shadow" d="{{ $body }}" transform="translate(8 8)" />

    <g class="pack__front">
        <g class="pack__peeks">
            <g transform="rotate(-10 66 122)">
                <g class="pack__peek" style="--peek: 0">
                    <rect class="pack__ink" x="41" y="60" width="50" height="62" rx="7" fill="#fffdf8" />
                    <path class="pack__scribble" d="M51 78 H81 M51 88 H73" />
                </g>
            </g>
            <g transform="rotate(2 112 122)">
                <g class="pack__peek" style="--peek: 1">
                    <rect class="pack__ink" x="87" y="56" width="50" height="62" rx="7" fill="#ffd166" />
                    <text class="pack__text" x="112" y="86" font-size="24">✓</text>
                </g>
            </g>
            <g transform="rotate(9 158 122)">
                <g class="pack__peek" style="--peek: 2">
                    <rect class="pack__ink" x="133" y="60" width="50" height="62" rx="7" fill="#d9f99d" />
                    <circle class="pack__ink" cx="171" cy="72" r="7" fill="#34d399" />
                    <path class="pack__scribble" d="M143 90 H171 M143 100 H163" />
                </g>
            </g>
        </g>

        <g class="pack__body">
            <path class="pack__ink" d="{{ $body }}" fill="#7dd3fc" />
            <svg x="13" y="51" width="198" height="222" overflow="hidden">
                <polygon points="118,0 170,0 50,222 -2,222" fill="#fffdf8" opacity=".24" />
                <polygon points="184,0 198,0 78,222 64,222" fill="#fffdf8" opacity=".24" />
                <polygon class="pack__sheen" points="-60,0 -22,0 -142,222 -180,222" fill="#fffdf8" opacity=".6" />
            </svg>
            <path class="pack__ink" d="M12 274 H212" fill="none" />
            <path class="pack__crimp" d="M18 287 H208" />

            <path class="pack__ink" d="M40 62 Q40 74 52 74 Q40 74 40 86 Q40 74 28 74 Q40 74 40 62 Z" fill="#fffdf8" />
            <path class="pack__ink" d="M192 168 Q192 177 201 177 Q192 177 192 186 Q192 177 183 177 Q192 177 192 168 Z" fill="#fffdf8" />

            <image href="{{ asset('logo.png') }}" x="24" y="96" width="176" height="56" transform="rotate(-7 112 124)" />

            <g transform="rotate(-5 68 236)">
                <rect class="pack__ink" x="20" y="223" width="96" height="26" rx="13" fill="#fffdf8" />
                <text class="pack__text" x="68" y="240.5" font-size="12.5" letter-spacing=".5">{{ $day }}</text>
            </g>

            <g transform="rotate(9 166 232)">
                <polygon class="pack__ink" points="{{ $burst }}" fill="#ffd166" />
                <text class="pack__text" x="166" y="237" font-size="28">25</text>
                <text class="pack__text" x="166" y="249" font-size="9.5">figurine</text>
            </g>
        </g>

        <g class="pack__flap">
            <path class="pack__ink" d="{{ $flap }}" fill="#7dd3fc" />
            <path class="pack__crimp" d="M18 33 H208" />
        </g>
    </g>
</svg>
