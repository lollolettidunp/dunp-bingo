<section wire:poll.5s x-data="{ specialCell: null, specialHelpOpen: false }" x-on:keydown.escape.window="specialHelpOpen = false">
    @if ($generationError)
        <div class="board-empty">
            <span class="board-empty__emoji" aria-hidden="true">🎲</span>
            <p class="board-empty__title">Nessuna scheda per oggi</p>
            <p class="board-empty__msg">{{ $generationError }}</p>
        </div>
    @else
        @php
            $status = $board->status;
            $marked = $board->markedCount();
            $hasBingo = $board->hasBingo();
            $remaining = $board->squaresFromBingo();
            $winning = $board->winningPositions();
            $locked = false;
            $canSubmit = $hasBingo && in_array($status, [\App\Models\Board::PLAYING, \App\Models\Board::REJECTED], true);
        @endphp

        <div class="board-status is-{{ $status }}">
            <div class="board-status__text">
                <strong class="board-status__title">
                    @switch($status)
                        @case(\App\Models\Board::PENDING) In revisione @break
                        @case(\App\Models\Board::APPROVED) Completata! 🏆 @break
                        @case(\App\Models\Board::REJECTED) Quasi! Ritocca e reinvia 💪 @break
                        @default {{ $hasBingo ? 'Hai fatto BINGO! 🎉' : 'In gioco' }}
                    @endswitch
                </strong>
                <span class="board-status__sub">
                    @switch($status)
                        @case(\App\Models\Board::PENDING) La tua scheda è in attesa di approvazione dall'admin. @break
                        @case(\App\Models\Board::APPROVED) Complimenti, il bingo è stato approvato. @break
                        @case(\App\Models\Board::REJECTED) Sei di nuovo in gioco: sistema quello che serve e reinvia quando vuoi. @break
                        @default {{ $hasBingo ? 'Invia la scheda in revisione per vincere.' : 'Segna le caselle man mano che succedono.' }}
                    @endswitch
                </span>
            </div>

            @if ($canSubmit)
                <button class="btn board-cta" wire:click="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">🚀 Invia in revisione</span>
                    <span wire:loading wire:target="submit">Invio…</span>
                </button>
            @endif
        </div>

        <details class="instructions">
            <summary>Istruzioni</summary>
            <div class="instructions__body">
                <p>Obiettivo: fai una riga, colonna o diagonale da 5.</p>
                <ul>
                    <li>Tocca una casella quando succede davvero.</li>
                    <li>Il centro è bonus: vale già segnato.</li>
                    <li>Quando compare BINGO, invia la scheda in revisione.</li>
                </ul>
            </div>
        </details>

        @if ($status === \App\Models\Board::REJECTED && $board->review_note)
            <div class="board-note" x-data="{ open: true }" x-show="open" x-collapse role="status">
                <div class="board-note__body">
                    <strong>Nota dell'admin</strong>
                    <p>{{ $board->review_note }}</p>
                </div>
                <button class="board-note__close" type="button" x-on:click="open = false" aria-label="Nascondi nota">×</button>
            </div>
        @endif

        @if ($board->notice)
            <div class="board-note" role="status">
                <div class="board-note__body">
                    <strong>Scheda aggiornata</strong>
                    <p>{{ $board->notice }}</p>
                </div>
            </div>
        @endif

        @unless ($locked)
            <div class="board-progress" role="group" aria-label="Avanzamento">
                <div class="board-progress__track">
                    <div class="board-progress__fill" style="transform: scaleX({{ number_format($marked / \App\Models\Board::MARKABLE, 3, '.', '') }})"></div>
                </div>
                <span class="board-progress__count">{{ $marked }}<small>/{{ \App\Models\Board::MARKABLE }}</small></span>
                @if (! $hasBingo && $remaining <= 2)
                    <span class="board-nudge">🔥 {{ $remaining === 1 ? 'Ti manca 1 casella' : "Ti mancano {$remaining} caselle" }} al BINGO!</span>
                @endif
            </div>
        @endunless

        <div class="board-play-area {{ $specialCells->isNotEmpty() && ! $locked ? 'has-specials' : '' }}">
            <div class="board-wrap {{ $locked ? 'is-locked' : '' }}">
                <div class="board {{ $locked ? 'readonly' : '' }}" x-bind:class="{ 'is-receiving-sticker': specialCell }">
                    @foreach ($board->cells as $cell)
                        <button type="button"
                            class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }} {{ in_array($cell->position, $winning, true) ? 'win' : '' }} {{ in_array($cell->cell_id, $specialAppliedCellIds, true) ? 'special-applied' : '' }} {{ $cell->marked_at && in_array($cell->cell_id, $specialAppliedCellIds, true) ? 'special-completed' : '' }} {{ ! $cell->marked_at && $cell->cell_id && in_array($cell->cell_id, $missedCellIds, true) ? 'missed-hint' : '' }}"
                            data-position="{{ $cell->position }}"
                            title="{{ $cell->position === 12 ? 'Bonus gratis' : ($locked ? 'Scheda bloccata durante la revisione' : ($cell->marked_at ? 'Tocca per togliere il segno' : 'Tocca per segnare questa casella')) }}"
                            x-on:dragover.prevent
                            x-on:drop.prevent="$wire.applySpecialCell(Number($event.dataTransfer.getData('text/plain')), {{ $cell->position }})"
                            x-on:click="if (specialCell) { $wire.applySpecialCell(specialCell, {{ $cell->position }}); specialCell = null; $event.preventDefault(); $event.stopImmediatePropagation(); }"
                            wire:click="toggle({{ $cell->position }})"
                            @disabled($cell->position === 12 || $locked)>
                            <span>{{ $cell->text }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($specialCells->isNotEmpty() && ! $locked)
                <aside class="special-rail" aria-labelledby="special-title" x-on:special-cell-applied.window="specialCell = null">
                    <header class="special-rail__head">
                        <h2 id="special-title">Adesivi del giorno</h2>
                        <div class="special-help" x-bind:class="{ 'is-open': specialHelpOpen }">
                            <button class="special-help__button" type="button" x-on:click="specialHelpOpen = ! specialHelpOpen" aria-label="Come funzionano gli adesivi" x-bind:aria-expanded="specialHelpOpen.toString()">?</button>
                            <div class="special-help__body" x-show="specialHelpOpen" x-cloak>
                                <strong>Come funzionano gli adesivi</strong>
                                <p>Questi sono eventi speciali ed imperdibili! <br> Trascinando un adesivo su una cella, la coprirai con questo nuovo evento.<br> Usa l'astuzia per semplificare la scheda e distruggi i tuoi colleghi sul tempo!</p>
                            </div>
                        </div>
                    </header>

                    <div class="special-rail__list">
                        @foreach ($specialCells as $special)
                            <button type="button" class="special-sticker" wire:key="special-sticker-{{ $special->id }}"
                                draggable="true"
                                data-special-cell-id="{{ $special->id }}"
                                title="Trascina sulla scheda"
                                x-bind:class="{ 'is-selected': specialCell === {{ $special->id }} }"
                                x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $special->id }}')"
                                x-on:click="specialCell = {{ $special->id }}">
                                <span>{{ $special->text }}</span>
                            </button>
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>

        <p class="hint">
            @if ($locked)
                Scheda bloccata durante la revisione.
            @else
                Click o tap per segnare. Il centro è bonus.
            @endif
        </p>
    @endif
</section>
