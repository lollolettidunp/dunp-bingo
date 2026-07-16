<section wire:poll.15s class="panel">
    <div class="panel-head">
        <h2>Consigli degli utenti</h2>
        <span class="spacer"></span>
        @if ($suggestions->isNotEmpty())
            <span class="mode-badge is-new">{{ $suggestions->count() }} in attesa</span>
        @endif
    </div>

    <div class="record-list">
        @forelse ($suggestions as $suggestion)
            <article class="record" wire:key="suggestion-{{ $suggestion->id }}">
                <span class="record-aside">
                    <span class="badge-diff d{{ $suggestion->difficulty }}">D{{ $suggestion->difficulty }}</span>
                </span>
                <span class="record-main">
                    <strong style="white-space:normal;">{{ $suggestion->text }}</strong>
                    <span>{{ $suggestion->user?->name ?? 'Utente rimosso' }} — {{ $suggestion->created_at->format('d/m/Y') }}</span>
                </span>
                <span class="record-aside review-actions">
                    <button class="btn is-lime" type="button" wire:click="publish({{ $suggestion->id }})">✓ Pubblica</button>
                    <button class="btn is-danger" type="button" wire:click="delete({{ $suggestion->id }})">✕ Elimina</button>
                </span>
            </article>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">💡</span>
                <p>Nessun consiglio in attesa</p>
                <small>Quando un utente consiglia una cella dalla sua scheda, comparirà qui.</small>
            </div>
        @endforelse
    </div>
</section>
