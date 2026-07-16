<section class="panel">
    <div class="panel-head">
        <h2>Presenze</h2>
        <span class="spacer"></span>
        <span class="mode-badge">Assenti = celle collegate disattivate</span>
    </div>

    <div class="record-list">
        @forelse ($users as $user)
            <div class="record" wire:key="attendance-{{ $user->id }}">
                <span class="record-main">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->excluded_cells_count }} {{ $user->excluded_cells_count === 1 ? 'cella collegata' : 'celle collegate' }}</span>
                </span>
                <span class="record-aside">
                    <span class="pill {{ $user->is_present ? 'on' : 'off' }}">{{ $user->is_present ? 'In ufficio' : 'Assente' }}</span>
                    <button
                        class="btn {{ $user->is_present ? 'is-ghost' : 'is-sky' }}"
                        type="button"
                        wire:click="togglePresence({{ $user->id }})"
                        wire:loading.attr="disabled"
                    >{{ $user->is_present ? 'Segna assente' : 'Rientrato' }}</button>
                </span>
            </div>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">🗓️</span>
                <p>Ancora nessun utente</p>
                <small>Crea gli utenti per gestirne le presenze.</small>
            </div>
        @endforelse
    </div>
</section>
