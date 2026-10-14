<section wire:poll.5s x-data="{ specialCell: null, specialHelpOpen: false }" x-on:keydown.escape.window="specialHelpOpen = false">
    @if ($generationError)
        <div class="board-empty">
            <span class="board-empty__emoji" aria-hidden="true">🎲</span>
            <p class="board-empty__title">Nessuna scheda per oggi</p>
            <p class="board-empty__msg">{{ $generationError }}</p>
            <button type="button" class="btn is-sky board-empty__retry" wire:click="openPack" wire:loading.attr="disabled">Riprova</button>
        </div>
    @elseif (! $board)
        <div class="pack-stage">
            {{-- @if ($regenerated)
                <h2 class="pack-stage__title">C'è una bustina nuova per te</h2>
                <p class="pack-stage__sub">L'admin ha rigenerato la tua scheda di oggi. Scartala per ripartire da capo.</p>
            @else
                <h2 class="pack-stage__title">La scheda di oggi è ancora nella bustina</h2>
                <p class="pack-stage__sub">Dentro ci sono 24 caselle più il bonus. Scartala quando sei pronto a giocare.</p>
            @endif --}}

            <button type="button" class="pack-opener" data-pack-opener wire:click="openPack" wire:loading.attr="disabled">
                <span class="pack-slot"><x-sticker-pack /></span>
                <span class="btn is-lime pack-opener__cta">
                    <span wire:loading.remove wire:target="openPack">Apri la bustina</span>
                    <span wire:loading wire:target="openPack">Sto scartando…</span>
                </span>
            </button>

            {{-- @if ($isAdmin)
                <div class="board-note pack-stage__admin" role="note">
                    <div class="board-note__body">
                        <strong>Per l'admin</strong>
                        <p>Qualcuno è rientrato o c'è una cella da sistemare? Fallo adesso: la tua scheda viene generata solo quando apri la bustina. <a href="{{ route('admin') }}">Vai al pannello admin</a></p>
                    </div>
                </div>
            @endif --}}
        </div>
    @else
        @php
            $status = $board->state();
            $marked = $board->markedCount();
            $remaining = $board->squaresFromBingo();
            $winning = $board->winningPositions();
            $locked = false;

            $submittable = count($board->submittableLines());
            $canSubmit = $submittable > 0;
            $approvedLines = $board->approvedLineCount();
            $pendingLines = $board->pendingLineCount();
            $claimedLines = $approvedLines + $pendingLines;
            $points = $board->points();
            // Nothing claimed yet: this submit is the one the speed multiplier rides on.
            $firstBingo = $claimedLines === 0;
            $multiplier = \App\Support\Scoring::multiplierFor(now());
            $extraValue = \App\Support\Scoring::format($submittable * \App\Support\Scoring::EXTRA_BINGO);
        @endphp

        <div class="board-status is-{{ $canSubmit ? 'ready' : $status }}">
            <div class="board-status__text">
                <strong class="board-status__title">
                    @if ($canSubmit)
                        {{ $firstBingo ? 'Hai fatto BINGO! 🎉' : 'Bingo extra pronto! 🎯' }}
                    @elseif ($status === \App\Models\BoardSubmission::PENDING)
                        In revisione
                    @elseif ($status === \App\Models\BoardSubmission::APPROVED)
                        Completata! 🏆
                    @elseif ($status === \App\Models\BoardSubmission::REJECTED)
                        Quasi! Ritocca e reinvia 💪
                    @else
                        In gioco
                    @endif
                </strong>
                <span class="board-status__sub">
                    @if ($canSubmit && $firstBingo)
                        Invia la scheda in revisione per vincere.
                    @elseif ($canSubmit)
                        {{ $submittable === 1 ? 'Hai una nuova linea' : "Hai {$submittable} nuove linee" }} da inviare: +{{ $extraValue }} punti.
                    @elseif ($status === \App\Models\BoardSubmission::PENDING)
                        La tua scheda è in attesa di approvazione dall'admin. Intanto continua: ogni linea in più vale 0,5.
                    @elseif ($status === \App\Models\BoardSubmission::APPROVED)
                        Bingo approvato. Continua a segnare: ogni linea in più vale 0,5 punti.
                    @elseif ($status === \App\Models\BoardSubmission::REJECTED)
                        Sei di nuovo in gioco: sistema quello che serve e reinvia quando vuoi.
                    @else
                        Segna le caselle man mano che succedono.
                    @endif
                </span>

                <span class="board-status__chips">
                    @if ($points > 0)
                        <span class="statetag s-approved">🏆 {{ \App\Support\Scoring::format($points) }} pt oggi</span>
                    @endif
                    @if ($approvedLines + $pendingLines > 0)
                        <span class="statetag s-playing">{{ $claimedLines }} {{ $claimedLines === 1 ? 'bingo inviato' : 'bingo inviati' }}</span>
                    @endif
                    @if ($firstBingo && $multiplier > 1)
                        <span class="statetag s-bonus">⚡ ×{{ \App\Support\Scoring::format($multiplier) }} se invii entro le {{ $multiplier >= 2 ? '11:00' : '15:00' }}</span>
                    @endif
                </span>
            </div>

            @if ($canSubmit)
                <button class="btn board-cta" wire:click="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">
                        🚀 {{ $firstBingo ? 'Invia in revisione' : 'Invia bingo extra' }}{{ $submittable > 1 ? " (×{$submittable})" : '' }}
                    </span>
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
                    <li>Il primo bingo vale 1 punto: <strong>×2</strong> se lo invii entro le 11:00, <strong>×1,5</strong> entro le 15:00.</li>
                    <li>Dopo il primo, continua a giocare: ogni linea in più vale <strong>0,5</strong> punti e si invia a parte.</li>
                </ul>
            </div>
        </details>

        @if ($board->lastRejection()?->review_note)
            <div class="board-note" x-data="{ open: true }" x-show="open" x-collapse role="status">
                <div class="board-note__body">
                    <strong>Nota dell'admin</strong>
                    <p>{{ $board->lastRejection()->review_note }}</p>
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
                @if (! $canSubmit && $remaining <= 2)
                    <span class="board-nudge">🔥 {{ $remaining === 1 ? 'Ti manca 1 casella' : "Ti mancano {$remaining} caselle" }} al BINGO!</span>
                @endif
            </div>
        @endunless

        @php $showSpecials = ($specialCells->isNotEmpty() || $placedSpecials->isNotEmpty()) && ! $locked; @endphp
        <div class="board-play-area {{ $showSpecials ? 'has-specials' : '' }}">
            <div class="board-wrap {{ $locked ? 'is-locked' : '' }}">
                <div class="board {{ $locked ? 'readonly' : '' }}" x-bind:class="{ 'is-receiving-sticker': specialCell }">
                    @foreach ($board->cells as $cell)
                        @php $movable = ! $locked && $placedSpecials->contains('position', $cell->position); @endphp
                        <button type="button"
                            class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }} {{ in_array($cell->position, $winning, true) ? 'win' : '' }} {{ in_array($cell->cell_id, $specialAppliedCellIds, true) ? 'special-applied' : '' }} {{ $cell->marked_at && in_array($cell->cell_id, $specialAppliedCellIds, true) ? 'special-completed' : '' }} {{ ! $cell->marked_at && $cell->cell_id && in_array($cell->cell_id, $missedCellIds, true) ? 'missed-hint' : '' }}"
                            data-position="{{ $cell->position }}"
                            @if ($movable)
                                draggable="true"
                                data-special-cell-id="{{ $cell->cell_id }}"
                                x-bind:class="{ 'is-move-source': specialCell === {{ $cell->cell_id }} }"
                            @endif
                            title="{{ $cell->position === 12 ? 'Bonus gratis' : ($locked ? 'Scheda bloccata durante la revisione' : ($cell->marked_at ? 'Tocca per togliere il segno' : 'Tocca per segnare questa casella')) }}{{ $movable ? ' · trascina per spostare l\'adesivo' : '' }}"
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

            @if ($showSpecials)
                <aside class="special-rail" aria-labelledby="special-title" x-on:special-cell-applied.window="specialCell = null">
                    <header class="special-rail__head">
                        <h2 id="special-title">Adesivi del giorno</h2>
                        <div class="special-help" x-bind:class="{ 'is-open': specialHelpOpen }">
                            <button class="special-help__button" type="button" x-on:click="specialHelpOpen = ! specialHelpOpen" aria-label="Come funzionano gli adesivi" x-bind:aria-expanded="specialHelpOpen.toString()">?</button>
                            <div class="special-help__body" x-show="specialHelpOpen" x-cloak>
                                <strong>Come funzionano gli adesivi</strong>
                                <p>Questi sono eventi speciali ed imperdibili! <br> Trascinando un adesivo su una cella, la coprirai con questo nuovo evento.<br> Usa l'astuzia per semplificare la scheda e distruggi i tuoi colleghi sul tempo!</p>
                                <p>Casella sbagliata? Trascina l'adesivo dalla scheda su un'altra casella, oppure toccalo in "Sulla scheda" e poi tocca la nuova casella: quella coperta torna com'era.</p>
                            </div>
                        </div>
                    </header>

                    @if ($specialCells->isNotEmpty())
                        <div class="special-rail__list">
                            @foreach ($specialCells as $special)
                                <button type="button" class="special-sticker" wire:key="special-sticker-{{ $special->id }}"
                                    draggable="true"
                                    data-special-cell-id="{{ $special->id }}"
                                    title="Trascina sulla scheda"
                                    x-bind:class="{ 'is-selected': specialCell === {{ $special->id }} }"
                                    x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $special->id }}')"
                                    x-on:click="specialCell = specialCell === {{ $special->id }} ? null : {{ $special->id }}">
                                    <span>{{ $special->text }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($placedSpecials->isNotEmpty())
                        <div class="special-rail__placed">
                            <h3 class="special-rail__label">Sulla scheda</h3>
                            <div class="special-rail__list">
                                @foreach ($placedSpecials as $placed)
                                    <button type="button" class="special-sticker is-placed" wire:key="placed-sticker-{{ $placed->cell_id }}"
                                        draggable="true"
                                        data-special-cell-id="{{ $placed->cell_id }}"
                                        title="Tocca e scegli la nuova casella per spostarlo"
                                        x-bind:class="{ 'is-selected': specialCell === {{ $placed->cell_id }} }"
                                        x-on:click="specialCell = specialCell === {{ $placed->cell_id }} ? null : {{ $placed->cell_id }}">
                                        <span>{{ $placed->text }}</span>
                                        <small class="special-sticker__tag">↔ Sposta</small>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
