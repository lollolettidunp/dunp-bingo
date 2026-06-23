<section>
    @if ($generationError)
        <div class="error">{{ $generationError }}</div>
    @else
        <div class="status-row">
            <div class="status"><span>Stato</span><strong>{{ $board->status }}</strong></div>
            @if ($board->hasBingo() && in_array($board->status, [\App\Models\Board::PLAYING, \App\Models\Board::REJECTED], true))
                <button class="button" wire:click="submit">Invia in revisione</button>
            @endif
        </div>
        <div class="board-wrap">
            <div class="board">
                @foreach ($board->cells as $cell)
                    <button type="button"
                        class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }}"
                        data-position="{{ $cell->position }}"
                        wire:dblclick="toggle({{ $cell->position }})"
                        wire:click="toggle({{ $cell->position }})"
                        @disabled($cell->position === 12 || in_array($board->status, [\App\Models\Board::PENDING, \App\Models\Board::APPROVED], true))>
                        <span>{{ $cell->text }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <p class="hint">Doppio click o tap per segnare. Il centro è bonus.</p>
    @endif
</section>
