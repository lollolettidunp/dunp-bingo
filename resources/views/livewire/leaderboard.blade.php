@php
    $ranked = $users->values();
    $top = $ranked->take(3);
    $rest = $ranked->slice(3);
    $meId = auth()->id();
    $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
    $lastWin = fn ($user) => $user->last_approved_at
        ? 'Ultimo bingo: ' . \Illuminate\Support\Carbon::parse($user->last_approved_at)->format('d/m/Y')
        : 'Ancora nessun bingo';
@endphp

<section class="panel">
    <div class="panel-head">
        <h2>Classifica</h2>
        <span class="spacer"></span>
        @if ($ranked->isNotEmpty())
            <span class="mode-badge is-new">{{ $ranked->count() }} {{ $ranked->count() === 1 ? 'giocatore' : 'giocatori' }}</span>
        @endif
    </div>

    <details class="instructions is-compact">
        <summary>Istruzioni</summary>
        <div class="instructions__body">
            <p>Ogni bingo approvato vale 1 punto.</p>
            <ul>
                <li>Contano solo le schede confermate dall'admin.</li>
                <li>Il numero grande è il totale punti.</li>
            </ul>
        </div>
    </details>

    @if ($ranked->isEmpty())
        <div class="empty">
            <span class="empty-emoji" aria-hidden="true">🏆</span>
            <p>Classifica vuota</p>
            <small>Il primo bingo approvato apre le danze.</small>
        </div>
    @else
        <ol class="podium" aria-label="Podio">
            @foreach ($top as $i => $user)
                @php $rank = $i + 1; $isMe = $user->id === $meId; @endphp
                <li class="podium__block r{{ $rank }} {{ $isMe ? 'is-me' : '' }}">
                    <div class="podium__person">
                        <span class="podium__medal" aria-hidden="true">{{ $medals[$rank] }}</span>
                        <x-avatar :user="$user" :size="$rank === 1 ? 62 : 50" />
                        <strong class="podium__name">
                            {{ $user->name }}
                            @if ($isMe) <span class="tu-tag">Tu</span> @endif
                        </strong>
                        <span class="podium__score">{{ $user->score }}<small>pt</small></span>
                    </div>
                    <div class="podium__pedestal"><span class="podium__rank">{{ $rank }}</span></div>
                </li>
            @endforeach
        </ol>

        @if ($rest->isNotEmpty())
            <ol class="lb-list" start="4">
                @foreach ($rest as $i => $user)
                    @php $rank = $i + 4; $isMe = $user->id === $meId; @endphp
                    <li class="lb-row {{ $isMe ? 'is-me' : '' }}" title="Punti totali di {{ $user->name }}">
                        <span class="lb-rank">{{ $rank }}</span>
                        <x-avatar :user="$user" :size="40" />
                        <span class="lb-main">
                            <strong>
                                {{ $user->name }}
                                @if ($isMe) <span class="tu-tag">Tu</span> @endif
                            </strong>
                            <span>{{ $lastWin($user) }}</span>
                        </span>
                        <span class="lb-score">{{ $user->score }}<small>pt</small></span>
                    </li>
                @endforeach
            </ol>
        @endif
    @endif
</section>
