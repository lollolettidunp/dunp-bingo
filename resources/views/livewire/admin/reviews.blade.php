<section class="panel">
    <h2>Revisioni</h2>
    <input type="text" wire:model="note" placeholder="nota rifiuto opzionale">
    @forelse ($boards as $board)
        <article class="review-card">
            <h3>{{ $board->user->name }} — {{ $board->submitted_at }}</h3>
            <p>{{ $board->cells->whereNotNull('marked_at')->count() }}/25 segnate</p>
            <button class="button" type="button" wire:click="approve({{ $board->id }})">Approva</button>
            <button type="button" wire:click="reject({{ $board->id }})">Rifiuta</button>
        </article>
    @empty
        <p>Nessun bingo in revisione.</p>
    @endforelse
</section>
