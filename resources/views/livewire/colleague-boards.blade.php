@php
    $statusClass = fn (\App\Models\Board $b) => match ($b->status) {
        \App\Models\Board::PENDING => 's-pending',
        \App\Models\Board::APPROVED => 's-approved',
        default => 's-playing',
    };
@endphp

<section wire:poll.15s class="grid-page">
    <aside class="panel">
        <div class="panel-head">
            <h2>Colleghi</h2>
            <span class="spacer"></span>
            @if ($boards->isNotEmpty())
                <span class="mode-badge is-new">{{ $boards->count() }} oggi</span>
            @endif
        </div>

        <details class="instructions is-compact">
            <summary>Istruzioni</summary>
            <div class="instructions__body">
                <p>Apri un collega per spiare la sua scheda.</p>
                <ul>
                    <li>Qui non puoi segnare caselle: guardi soltanto.</li>
                    <li>Le etichette dicono se sta giocando, è in revisione o ha finito.</li>
                </ul>
            </div>
        </details>

        <div class="record-list">
            @forelse ($boards as $board)
                @php $isWin = $board->hasBingo(); @endphp
                <button
                    class="record {{ $selected && $selected->id === $board->id ? 'is-active' : '' }}"
                    type="button"
                    title="Apri la scheda di {{ $board->user->name }}"
                    wire:click="select({{ $board->id }})"
                    wire:key="cb-{{ $board->id }}"
                    @if ($selected && $selected->id === $board->id) aria-current="true" @endif
                >
                    <x-avatar :user="$board->user" :size="40" />
                    <span class="record-main">
                        <strong>{{ $board->user->name }} @if ($isWin) <span aria-label="bingo">🎉</span> @endif</strong>
                        <span>{{ $board->markedCount() }}/{{ \App\Models\Board::MARKABLE }} segnate</span>
                    </span>
                    <span class="record-aside">
                        <span class="statetag {{ $statusClass($board) }}">{{ $board->statusLabel() }}</span>
                    </span>
                </button>
            @empty
                <div class="empty">
                    <span class="empty-emoji" aria-hidden="true">🫥</span>
                    <p>Nessuna scheda oggi</p>
                    <small>Quando i colleghi iniziano a giocare, le loro schede compaiono qui.</small>
                </div>
            @endforelse
        </div>
    </aside>

    <div class="board-wrap">
        @if ($selected)
            @php
                $marked = $selected->markedCount();
                $winning = $selected->winningPositions();
            @endphp
            <div class="cb-detail-head">
                <x-avatar :user="$selected->user" :size="54" />
                <div class="cb-detail-head__text">
                    <strong>{{ $selected->user->name }}</strong>
                    <span class="statetag {{ $statusClass($selected) }}">{{ $selected->statusLabel() }}</span>
                </div>
            </div>

            <div class="board-progress" role="group" aria-label="Avanzamento di {{ $selected->user->name }}">
                <div class="board-progress__track">
                    <div class="board-progress__fill" style="transform: scaleX({{ number_format($marked / \App\Models\Board::MARKABLE, 3, '.', '') }})"></div>
                </div>
                <span class="board-progress__count">{{ $marked }}<small>/{{ \App\Models\Board::MARKABLE }}</small></span>
            </div>

            <div class="board readonly">
                @foreach ($selected->cells as $cell)
                    <div class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }} {{ in_array($cell->position, $winning, true) ? 'win' : '' }}" title="{{ $cell->position === 12 ? 'Bonus gratis' : ($cell->marked_at ? 'Casella segnata' : 'Casella non segnata') }}">
                        <span>{{ $cell->text }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">👀</span>
                <p>Nessuna scheda da mostrare</p>
                <small>Seleziona un collega dalla lista per vedere la sua scheda.</small>
            </div>
        @endif
    </div>
</section>
