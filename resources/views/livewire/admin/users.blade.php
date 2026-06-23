<section class="panel">
    <div class="panel-head">
        <h2>Utenti</h2>
        <span class="spacer"></span>
        @if ($userId)
            <span class="mode-badge">Modifica: {{ $name ?: 'utente' }}</span>
        @else
            <span class="mode-badge is-new">Nuovo utente</span>
        @endif
    </div>

    <form wire:submit="save" class="form-grid">
        <div class="field-row">
            <div class="field">
                <label for="user-name">Nome</label>
                <input id="user-name" type="text" wire:model="name" placeholder="Mario Rossi" @error('name') aria-invalid="true" @enderror>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="user-email">Email</label>
                <input id="user-email" type="email" wire:model="email" placeholder="mario@azienda.it" @error('email') aria-invalid="true" @enderror>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="user-score">Punti iniziali</label>
                <input id="user-score" type="number" min="0" wire:model="startingScore" placeholder="0" @error('startingScore') aria-invalid="true" @enderror>
                @error('startingScore') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="field" style="align-self: end;">
                <span class="field-label">Stato</span>
                <label class="toggle"><input type="checkbox" wire:model="isEnabled"> Abilitato a giocare</label>
            </div>
        </div>

        <div class="review-actions">
            <button class="btn is-sky" type="submit">{{ $userId ? 'Salva modifiche' : 'Crea utente' }}</button>
            @if ($userId)
                <button class="btn is-ghost" type="button" wire:click="cancel">Annulla</button>
            @endif
        </div>
    </form>

    <div class="record-list">
        @forelse ($users as $user)
            <button class="record" type="button" wire:click="edit({{ $user->id }})" wire:key="user-{{ $user->id }}">
                <span class="record-main">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                </span>
                <span class="record-aside">
                    <span class="review-count">{{ $user->starting_score }} pt</span>
                    <span class="pill {{ $user->is_enabled ? 'on' : 'off' }}">{{ $user->is_enabled ? 'On' : 'Off' }}</span>
                </span>
            </button>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">👥</span>
                <p>Ancora nessun utente</p>
                <small>Crea il primo utente con il modulo qui sopra.</small>
            </div>
        @endforelse
    </div>
</section>
