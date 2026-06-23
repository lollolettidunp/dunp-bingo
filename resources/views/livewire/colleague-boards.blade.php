<section wire:poll.15s class="grid-page">
    <aside class="panel">
        <h2>Colleghi</h2>
        @forelse ($boards as $board)
            <button class="list-button" type="button" wire:click="select({{ $board->id }})">
                {{ $board->user->name }} — {{ $board->cells->whereNotNull('marked_at')->count() }}/25 — {{ $board->status }}
            </button>
        @empty
            <p>Nessuna scheda oggi.</p>
        @endforelse
    </aside>

    <div class="board-wrap">
        @if ($selected)
            <h2>{{ $selected->user->name }}</h2>
            <div class="board readonly">
                @foreach ($selected->cells as $cell)
                    <div class="cell {{ $cell->marked_at ? 'marked' : '' }} {{ $cell->position === 12 ? 'bonus' : '' }}"><span>{{ $cell->text }}</span></div>
                @endforeach
            </div>
        @else
            <p>Nessuna scheda da mostrare.</p>
        @endif
    </div>
</section>
