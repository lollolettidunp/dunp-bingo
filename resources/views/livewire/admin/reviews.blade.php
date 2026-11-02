<section wire:poll.5s class="panel">
    <div class="panel-head">
        <h2>Revisioni</h2>
        <span class="spacer"></span>
        @if ($submissions->isNotEmpty())
            <span class="mode-badge is-new">{{ $submissions->count() }} in attesa</span>
        @endif
    </div>

    <div class="stack" style="gap: 12px;">
        @forelse ($submissions as $submission)
            @php
                $board = $submission->board;
                $award = $submission->award();
                $isExtra = $board->approvedLineCount() > 0;
                $stillValid = array_diff($submission->lines, $board->completedLines()) === [];
            @endphp
            <article class="review-card" x-data="{ rejecting: false }" wire:key="submission-{{ $submission->id }}">
                <div class="review-head">
                    <h3>{{ $board->user->name }}</h3>
                    <span class="review-count">{{ $board->cells->whereNotNull('marked_at')->count() }}/25 segnate</span>
                </div>
                <p class="review-meta">
                    Inviata {{ $submission->submitted_at?->format('d/m/Y \a\l\l\e H:i') ?? '—' }}
                </p>
                <p class="review-award">
                    <span class="statetag {{ $isExtra ? 's-playing' : 's-approved' }}">
                        {{ $isExtra ? 'Bingo extra' : 'Primo bingo' }} · {{ $submission->lineCount() }} {{ $submission->lineCount() === 1 ? 'linea' : 'linee' }}
                    </span>
                    <strong>+{{ \App\Support\Scoring::format($award['points']) }} pt</strong>
                    @if ($award['multiplier'] > 1)
                        <small>({{ \App\Support\Scoring::format($award['base']) }} × {{ \App\Support\Scoring::format($award['multiplier']) }} per l'orario di invio)</small>
                    @endif
                </p>
                @unless ($stillValid)
                    <p class="review-warning">⚠️ Le caselle sono cambiate dopo l'invio: sulla scheda attuale la linea non è più completa.</p>
                @endunless

                <div class="review-actions">
                    <button class="btn is-lime" type="button" wire:click="approve({{ $submission->id }})">✓ Approva</button>
                    <button class="btn is-danger" type="button" x-on:click="rejecting = !rejecting" x-bind:aria-expanded="rejecting">✕ Rifiuta</button>
                    <button class="btn is-ghost" type="button" wire:click="preview({{ $submission->id }})">Vedi scheda</button>
                </div>

                <div class="review-reject" x-show="rejecting" x-collapse x-cloak>
                    <label for="note-{{ $submission->id }}">Nota di rifiuto <span style="font-weight:700;">(opzionale)</span></label>
                    <input id="note-{{ $submission->id }}" type="text" wire:model="note" placeholder="Perché viene rifiutata?">
                    <div class="review-reject-actions">
                        <button class="btn is-danger" type="button" wire:click="reject({{ $submission->id }})" x-on:click="rejecting = false">Conferma rifiuto</button>
                        <button class="btn is-ghost" type="button" x-on:click="rejecting = false">Annulla</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">🎉</span>
                <p>Nessun bingo in revisione</p>
                <small>Quando qualcuno fa BINGO, la sua scheda compare qui.</small>
            </div>
        @endforelse
    </div>

    @if ($previewSubmission)
        @php
            $previewBoard = $previewSubmission->board;
            // Only the squares of the lines being claimed, so the reviewer checks the right five.
            $claimed = collect($previewSubmission->lines)->flatMap(fn ($line) => \App\Models\Board::LINES[$line])->unique()->all();
        @endphp
        <div class="modal-backdrop" wire:click="closePreview">
            <section class="modal" wire:click.stop role="dialog" aria-modal="true" aria-label="Scheda di {{ $previewBoard->user->name }}">
                <button class="btn is-ghost dialog-close" type="button" wire:click="closePreview" aria-label="Chiudi">×</button>
                <div class="panel-head">
                    <h3 style="margin:0;">Scheda di {{ $previewBoard->user->name }}</h3>
                </div>
                <p class="review-meta" style="margin-bottom:12px;">
                    {{ $previewBoard->statusLabel() }} — {{ $previewBoard->cells->whereNotNull('marked_at')->count() }}/25 segnate —
                    in revisione {{ $previewSubmission->lineCount() }} {{ $previewSubmission->lineCount() === 1 ? 'linea' : 'linee' }}
                </p>
                <div class="board readonly">
                    @foreach ($previewBoard->cells as $cell)
                        <div class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }} {{ in_array($cell->position, $claimed, true) ? 'win' : '' }}"><span>{{ $cell->text }}</span></div>
                    @endforeach
                </div>
            </section>
        </div>
    @endif
</section>
