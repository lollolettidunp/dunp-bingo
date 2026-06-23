<section class="panel">
    <div class="panel-head">
        <h2>Revisioni</h2>
        <span class="spacer"></span>
        @if ($boards->isNotEmpty())
            <span class="mode-badge is-new">{{ $boards->count() }} in attesa</span>
        @endif
    </div>

    <div class="stack" style="gap: 12px;">
        @forelse ($boards as $board)
            <article class="review-card" x-data="{ rejecting: false }">
                <div class="review-head">
                    <h3>{{ $board->user->name }}</h3>
                    <span class="review-count">{{ $board->cells->whereNotNull('marked_at')->count() }}/25 segnate</span>
                </div>
                <p class="review-meta">
                    Inviata {{ $board->submitted_at?->format('d/m/Y \a\l\l\e H:i') ?? '—' }}
                </p>

                <div class="review-actions">
                    <button class="btn is-lime" type="button" wire:click="approve({{ $board->id }})">✓ Approva</button>
                    <button class="btn is-danger" type="button" x-on:click="rejecting = !rejecting" x-bind:aria-expanded="rejecting">✕ Rifiuta</button>
                    <button class="btn is-ghost" type="button" wire:click="preview({{ $board->id }})">Vedi scheda</button>
                </div>

                <div class="review-reject" x-show="rejecting" x-collapse x-cloak>
                    <label for="note-{{ $board->id }}">Nota di rifiuto <span style="font-weight:700;">(opzionale)</span></label>
                    <input id="note-{{ $board->id }}" type="text" wire:model="note" placeholder="Perché viene rifiutata?">
                    <div class="review-reject-actions">
                        <button class="btn is-danger" type="button" wire:click="reject({{ $board->id }})" x-on:click="rejecting = false">Conferma rifiuto</button>
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

    @if ($previewBoard)
        <div class="modal-backdrop" wire:click="closePreview">
            <section class="modal" wire:click.stop role="dialog" aria-modal="true" aria-label="Scheda di {{ $previewBoard->user->name }}">
                <button class="btn is-ghost dialog-close" type="button" wire:click="closePreview" aria-label="Chiudi">×</button>
                <div class="panel-head">
                    <h3 style="margin:0;">Scheda di {{ $previewBoard->user->name }}</h3>
                </div>
                <p class="review-meta" style="margin-bottom:12px;">
                    {{ $previewBoard->statusLabel() }} — {{ $previewBoard->cells->whereNotNull('marked_at')->count() }}/25 segnate
                </p>
                <div class="board readonly">
                    @foreach ($previewBoard->cells as $cell)
                        <div class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }}"><span>{{ $cell->text }}</span></div>
                    @endforeach
                </div>
            </section>
        </div>
    @endif
</section>
